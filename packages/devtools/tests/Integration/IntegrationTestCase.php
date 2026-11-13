<?php

declare(strict_types=1);

namespace Tests\Integration;

use Davingm\LaravelDevtools\DevtoolsServiceProvider;
use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use RuntimeException;

abstract class IntegrationTestCase extends TestCase
{
    protected string $appBasePath;

    protected string $dbPath;

    private bool $hadPreviousTestbenchBasePath = false;

    private ?string $previousTestbenchBasePath = null;

    private ?Closure $appAutoloader = null;

    protected function getPackageProviders($app): array
    {
        return [DevtoolsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => $this->dbPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('view.paths', [$this->appBasePath.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views']);
        $app['config']->set('view.compiled', $this->appBasePath.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'views');
    }

    protected function setUp(): void
    {
        $this->appBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'devtools-testbench-'.bin2hex(random_bytes(8));
        $dbPath = tempnam(sys_get_temp_dir(), 'devtools-db-');

        if ($dbPath === false) {
            throw new RuntimeException('Unable to create the temporary SQLite database file.');
        }

        $this->dbPath = $dbPath;

        $this->hadPreviousTestbenchBasePath = array_key_exists('TESTBENCH_APP_BASE_PATH', $_ENV);
        $this->previousTestbenchBasePath = $_ENV['TESTBENCH_APP_BASE_PATH'] ?? null;
        $_ENV['TESTBENCH_APP_BASE_PATH'] = $this->appBasePath;

        $this->createApplicationDirectories();
        $this->createApplicationStubs();

        parent::setUp();

        $this->appAutoloader = function (string $class): void {
            if (! str_starts_with($class, 'App\\')) {
                return;
            }

            $classPath = substr($class, strlen('App\\'));
            $file = $this->appBasePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $classPath).'.php';

            if (is_file($file)) {
                require $file;
            }
        };
        spl_autoload_register($this->appAutoloader);

        $this->createTestTables();
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->app) && $this->app->bound('db')) {
                DB::disconnect('testing');
            }

            parent::tearDown();
        } finally {
            if ($this->appAutoloader !== null) {
                spl_autoload_unregister($this->appAutoloader);
            }

            if ($this->hadPreviousTestbenchBasePath) {
                $_ENV['TESTBENCH_APP_BASE_PATH'] = $this->previousTestbenchBasePath;
            } else {
                unset($_ENV['TESTBENCH_APP_BASE_PATH']);
            }

            if (is_file($this->dbPath)) {
                unlink($this->dbPath);
            }

            $this->deleteDirectory($this->appBasePath);
        }
    }

    private function createApplicationDirectories(): void
    {
        foreach ([
            'app/Models',
            'app/Http/Controllers',
            'resources/views/layouts',
            'resources/views/pages/products',
            'bootstrap/cache',
            'storage/framework/views',
            'routes',
        ] as $directory) {
            $path = $this->appBasePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $directory);

            if (! is_dir($path) && ! mkdir($path, 0777, true) && ! is_dir($path)) {
                throw new RuntimeException("Unable to create temporary application directory: {$path}");
            }
        }
    }

    private function createApplicationStubs(): void
    {
        file_put_contents(
            $this->appBasePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Controller.php',
            "<?php\n\nnamespace App\\Http\\Controllers;\n\nabstract class Controller extends \\Illuminate\\Routing\\Controller {}\n"
        );

        file_put_contents(
            $this->appBasePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR.'Category.php',
            "<?php\n\nnamespace App\\Models;\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\nclass Category extends Model\n{\n    protected \$fillable = ['name'];\n}\n"
        );

        file_put_contents(
            $this->appBasePath.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.'app.blade.php',
            '<!DOCTYPE html><html><body>@yield(\'content\')</body></html>'
        );

        file_put_contents(
            $this->appBasePath.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php',
            "<?php\n"
        );
    }

    private function createTestTables(): void
    {
        Schema::connection('testing')->create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('testing')->create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = new \FilesystemIterator($path);

        foreach ($items as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
                continue;
            }

            if (! unlink($item->getPathname())) {
                throw new RuntimeException("Unable to remove temporary test file: {$item->getPathname()}");
            }
        }

        if (! rmdir($path)) {
            throw new RuntimeException("Unable to remove temporary application directory: {$path}");
        }
    }
}

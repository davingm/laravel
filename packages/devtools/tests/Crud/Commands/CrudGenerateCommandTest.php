<?php

declare(strict_types=1);

namespace Tests\Crud\Commands;

use Davingm\LaravelDevtools\Commands\CrudGenerateCommand;
use Davingm\LaravelDevtools\Crud\Generators\ControllerGenerator;
use Davingm\LaravelDevtools\Crud\Generators\ControllerWriter;
use Davingm\LaravelDevtools\Crud\Generators\ModelGenerator;
use Davingm\LaravelDevtools\Crud\Generators\ModelWriter;
use Davingm\LaravelDevtools\Crud\Generators\RouteManager;
use Davingm\LaravelDevtools\Crud\Generators\RouteStub;
use Davingm\LaravelDevtools\Crud\Generators\ViewGenerator;
use Davingm\LaravelDevtools\Crud\Generators\ViewWriter;
use Davingm\LaravelDevtools\Crud\Schema\SchemaInspector;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CrudGenerateCommandTest extends TestCase
{
    private string $tempDir;
    private string $routesFile;
    private array $paths;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'crud-command-'.bin2hex(random_bytes(8));
        $this->paths = [
            'models' => $this->tempDir.DIRECTORY_SEPARATOR.'Models',
            'controllers' => $this->tempDir.DIRECTORY_SEPARATOR.'Controllers',
            'views' => $this->tempDir.DIRECTORY_SEPARATOR.'views',
        ];
        foreach ($this->paths as $path) {
            mkdir($path, 0777, true);
        }
        $this->routesFile = $this->tempDir.DIRECTORY_SEPARATOR.'routes.php';
        file_put_contents($this->routesFile, "<?php\n\n");

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $container = new CommandTestContainer();
        $container->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstance('db.schema');

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
        });
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function test_generates_all_files_for_simple_table(): void
    {
        [$status, $output] = $this->runCommand('Product');

        self::assertSame(0, $status);
        self::assertFileExists($this->paths['models'].DIRECTORY_SEPARATOR.'Product.php');
        self::assertFileExists($this->paths['controllers'].DIRECTORY_SEPARATOR.'ProductController.php');
        self::assertFileExists($this->paths['views'].DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.'index.blade.php');
        self::assertFileExists($this->paths['views'].DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.'create.blade.php');
        self::assertFileExists($this->paths['views'].DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.'edit.blade.php');
        self::assertStringContainsString('// BEGIN auto-crud:products', file_get_contents($this->routesFile));
        self::assertStringContainsString('5 files created', $output);
        self::assertStringContainsString("Added route block for 'products'", $output);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $originalRoutes = file_get_contents($this->routesFile);
        [$status, $output] = $this->runCommand('Product', ['--dry-run' => true]);

        self::assertSame(0, $status);
        self::assertSame([], glob($this->paths['models'].DIRECTORY_SEPARATOR.'*'));
        self::assertSame([], glob($this->paths['controllers'].DIRECTORY_SEPARATOR.'*'));
        self::assertSame($originalRoutes, file_get_contents($this->routesFile));
        self::assertStringContainsString('Would create app/Models/Product.php', $output);
        self::assertStringContainsString('Preview:', $output);
        self::assertStringNotContainsString('// BEGIN auto-crud:products', $output);
    }

    public function test_force_overwrites_existing_file(): void
    {
        $modelPath = $this->paths['models'].DIRECTORY_SEPARATOR.'Product.php';
        file_put_contents($modelPath, 'old');

        [$status, $output] = $this->runCommand('Product', ['--force' => true]);

        self::assertSame(0, $status);
        self::assertNotSame('old', file_get_contents($modelPath));
        self::assertStringContainsString('Updated app/Models/Product.php', $output);
    }

    public function test_skips_existing_files_without_force(): void
    {
        $modelPath = $this->paths['models'].DIRECTORY_SEPARATOR.'Product.php';
        file_put_contents($modelPath, 'keep this');

        [$status, $output] = $this->runCommand('Product');

        self::assertSame(0, $status);
        self::assertSame('keep this', file_get_contents($modelPath));
        self::assertStringContainsString('Skipped app/Models/Product.php', $output);
        self::assertStringContainsString('1 file skipped', $output);
    }

    public function test_force_and_dry_run_conflict(): void
    {
        [$status, $output] = $this->runCommand('Product', ['--dry-run' => true, '--force' => true]);

        self::assertSame(1, $status);
        self::assertStringContainsString('Cannot combine --force with --dry-run.', $output);
        self::assertFileDoesNotExist($this->paths['models'].DIRECTORY_SEPARATOR.'Product.php');
    }

    public function test_aborts_on_schema_failure(): void
    {
        [$status, $output] = $this->runCommand('Missing');

        self::assertSame(1, $status);
        self::assertStringContainsString('Schema inspection failed:', $output);
        self::assertSame([], glob($this->paths['models'].DIRECTORY_SEPARATOR.'*'));
        self::assertSame("<?php\n\n", file_get_contents($this->routesFile));
    }

    public function test_continues_after_writer_failure(): void
    {
        $invalidModelBase = $this->tempDir.DIRECTORY_SEPARATOR.'not-a-directory';
        file_put_contents($invalidModelBase, 'file blocks directory creation');
        $paths = $this->paths;
        $paths['models'] = $invalidModelBase;

        set_error_handler(static fn (): bool => true);
        try {
            [$status, $output] = $this->runCommand('Product', ['--force' => true], $paths);
        } finally {
            restore_error_handler();
        }

        self::assertSame(1, $status);
        self::assertStringContainsString('Failed app/Models/Product.php:', $output);
        self::assertFileExists($this->paths['controllers'].DIRECTORY_SEPARATOR.'ProductController.php');
        self::assertFileExists($this->paths['views'].DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.'index.blade.php');
        self::assertStringContainsString('// BEGIN auto-crud:products', file_get_contents($this->routesFile));
    }

    public function test_reports_summary_counts(): void
    {
        [, $output] = $this->runCommand('Product');

        self::assertStringContainsString('Summary:', $output);
        self::assertStringContainsString('5 files created', $output);
        self::assertStringContainsString('0 errors', $output);
    }

    public function test_normalizes_studlycase_input(): void
    {
        [$status] = $this->runCommand('Product');

        self::assertSame(0, $status);
        self::assertStringContainsString('// BEGIN auto-crud:products', file_get_contents($this->routesFile));
    }

    public function test_normalizes_snake_case_input(): void
    {
        [$status] = $this->runCommand('products');

        self::assertSame(0, $status);
        self::assertFileExists($this->paths['models'].DIRECTORY_SEPARATOR.'Product.php');
        self::assertStringContainsString('// BEGIN auto-crud:products', file_get_contents($this->routesFile));
    }

    public function test_controller_uses_default_pagination(): void
    {
        [$status] = $this->runCommand('Product');

        self::assertSame(0, $status);
        self::assertStringContainsString('paginate(15)', file_get_contents($this->paths['controllers'].DIRECTORY_SEPARATOR.'ProductController.php'));
    }

    /** @param array<string, bool> $options @param array{models: string, controllers: string, views: string}|null $paths @return array{int, string} */
    private function runCommand(string $model, array $options = [], ?array $paths = null): array
    {
        $paths ??= $this->paths;
        $command = new CrudGenerateCommand(
            inspector: new SchemaInspector(),
            modelGenerator: new ModelGenerator(),
            controllerGenerator: new ControllerGenerator(),
            viewGenerator: new ViewGenerator(),
            routeManager: new RouteManager($this->routesFile),
            routeStub: new RouteStub(),
            modelWriter: new ModelWriter($paths['models']),
            controllerWriter: new ControllerWriter($paths['controllers']),
            viewWriter: new ViewWriter($paths['views']),
            paths: [
                'models' => $paths['models'],
                'controllers' => $paths['controllers'],
                'views' => $paths['views'],
                'routes' => $this->routesFile,
            ],
        );
        $command->setLaravel(Facade::getFacadeApplication());
        $tester = new CommandTester($command);
        $arguments = ['model' => $model];
        foreach ($options as $option => $value) {
            $arguments[$option] = $value;
        }
        $status = $tester->execute($arguments);

        return [$status, $tester->getDisplay()];
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $entryPath = $path.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($entryPath)) {
                $this->removeDirectory($entryPath);
            } else {
                chmod($entryPath, 0666);
                unlink($entryPath);
            }
        }
        rmdir($path);
    }
}

final class CommandTestContainer extends Container
{
    public function runningUnitTests(): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Exceptions\ViewGenerationException;
use Davingm\LaravelDevtools\Crud\Generators\ViewGenerator;
use Davingm\LaravelDevtools\Crud\Generators\ViewWriter;
use Davingm\LaravelDevtools\Crud\Schema\SchemaInspector;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;

final class ViewWriterTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'devtools-view-writer-'.bin2hex(random_bytes(8));

        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $container = $capsule->getContainer();
        $container->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstance('db.schema');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
        parent::tearDown();
    }

    public function test_writes_to_new_file(): void
    {
        $path = (new ViewWriter($this->basePath))->write('products', 'index', 'view content');

        self::assertSame(realpath($this->basePath).DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.'index.blade.php', $path);
        self::assertSame('view content', file_get_contents($path));
    }

    public function test_refuses_to_overwrite_by_default(): void
    {
        $writer = new ViewWriter($this->basePath);
        $path = $writer->write('products', 'index', 'original');

        try {
            $writer->write('products', 'index', 'replacement');
            self::fail('Existing files must not be overwritten by default.');
        } catch (ViewGenerationException $exception) {
            self::assertSame("Refusing to overwrite existing file: {$path}", $exception->getMessage());
        }

        self::assertSame('original', file_get_contents($path));
    }

    public function test_allows_overwrite_when_requested(): void
    {
        $writer = new ViewWriter($this->basePath);
        $path = $writer->write('products', 'index', 'original');

        $writer->write('products', 'index', 'replacement', true);

        self::assertSame('replacement', file_get_contents($path));
    }

    public function test_creates_missing_directories(): void
    {
        $path = (new ViewWriter($this->basePath))->write('products', 'create', 'view');

        self::assertFileExists($path);
    }

    public function test_writes_three_files_for_one_table(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        $views = (new ViewGenerator())->generate((new SchemaInspector())->inspect('products'));
        $writer = new ViewWriter($this->basePath);
        $paths = [];
        foreach ($views as $viewName => $content) {
            $paths[] = $writer->write('products', $viewName, $content);
        }

        self::assertCount(3, $paths);
        self::assertFileExists($this->basePath.DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.'index.blade.php');
        self::assertFileExists($this->basePath.DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.'create.blade.php');
        self::assertFileExists($this->basePath.DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.'edit.blade.php');
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $entryPath = $path.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($entryPath)) {
                $this->removeDirectory($entryPath);
            } else {
                unlink($entryPath);
            }
        }
        rmdir($path);
    }
}

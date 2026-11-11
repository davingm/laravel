<?php

declare(strict_types=1);

namespace Tests\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Exceptions\ControllerGenerationException;
use Davingm\LaravelDevtools\Crud\Generators\ControllerWriter;
use PHPUnit\Framework\TestCase;

final class ControllerWriterTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'devtools-controller-writer-'.bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
        parent::tearDown();
    }

    public function test_writes_to_new_file(): void
    {
        $path = (new ControllerWriter($this->basePath))->write('ProductController', '<?php class ProductController {}');

        self::assertSame(realpath($this->basePath).DIRECTORY_SEPARATOR.'ProductController.php', $path);
        self::assertSame('<?php class ProductController {}', file_get_contents($path));
    }

    public function test_refuses_to_overwrite_by_default(): void
    {
        $writer = new ControllerWriter($this->basePath);
        $path = $writer->write('ProductController', 'original');

        try {
            $writer->write('ProductController', 'replacement');
            self::fail('Existing files must not be overwritten by default.');
        } catch (ControllerGenerationException $exception) {
            self::assertSame("Refusing to overwrite existing file: {$path}", $exception->getMessage());
        }

        self::assertSame('original', file_get_contents($path));
    }

    public function test_allows_overwrite_when_requested(): void
    {
        $writer = new ControllerWriter($this->basePath);
        $path = $writer->write('ProductController', 'original');

        $writer->write('ProductController', 'replacement', true);

        self::assertSame('replacement', file_get_contents($path));
    }

    public function test_creates_missing_directories(): void
    {
        $nestedPath = $this->basePath.DIRECTORY_SEPARATOR.'nested'.DIRECTORY_SEPARATOR.'controllers';

        $path = (new ControllerWriter($nestedPath))->write('ProductController', 'controller');

        self::assertFileExists($path);
        self::assertSame('controller', file_get_contents($path));
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

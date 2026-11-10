<?php

declare(strict_types=1);

namespace Tests\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Exceptions\ModelGenerationException;
use Davingm\LaravelDevtools\Crud\Generators\ModelWriter;
use PHPUnit\Framework\TestCase;

final class ModelWriterTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'devtools-model-writer-'.bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
        parent::tearDown();
    }

    public function test_writes_to_new_file(): void
    {
        $path = (new ModelWriter($this->basePath))->write('Product', '<?php class Product {}');

        $this->assertSame(realpath($this->basePath).DIRECTORY_SEPARATOR.'Product.php', $path);
        $this->assertSame('<?php class Product {}', file_get_contents($path));
    }

    public function test_refuses_to_overwrite_by_default(): void
    {
        $writer = new ModelWriter($this->basePath);
        $path = $writer->write('Product', 'original');

        try {
            $writer->write('Product', 'replacement');
            $this->fail('Existing files must not be overwritten by default.');
        } catch (ModelGenerationException $exception) {
            $this->assertSame("Refusing to overwrite existing file: {$path}", $exception->getMessage());
        }

        $this->assertSame('original', file_get_contents($path));
    }

    public function test_allows_overwrite_when_requested(): void
    {
        $writer = new ModelWriter($this->basePath);
        $path = $writer->write('Product', 'original');

        $writer->write('Product', 'replacement', true);

        $this->assertSame('replacement', file_get_contents($path));
    }

    public function test_creates_missing_directories(): void
    {
        $nestedPath = $this->basePath.DIRECTORY_SEPARATOR.'nested'.DIRECTORY_SEPARATOR.'models';

        $path = (new ModelWriter($nestedPath))->write('Product', 'model');

        $this->assertFileExists($path);
        $this->assertSame('model', file_get_contents($path));
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

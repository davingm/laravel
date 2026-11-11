<?php

declare(strict_types=1);

namespace Tests\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Data\ColumnDefinition;
use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Davingm\LaravelDevtools\Crud\Exceptions\RouteManagerException;
use Davingm\LaravelDevtools\Crud\Generators\RouteManager;
use Davingm\LaravelDevtools\Crud\Generators\RouteRegistrationResult;
use PHPUnit\Framework\TestCase;

final class RouteManagerTest extends TestCase
{
    private string $directory;
    private string $routesFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'devtools-route-manager-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0777, true);
        $this->routesFile = $this->directory.DIRECTORY_SEPARATOR.'web.php';
    }

    protected function tearDown(): void
    {
        if (is_file($this->routesFile)) {
            chmod($this->routesFile, 0666);
            unlink($this->routesFile);
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
        parent::tearDown();
    }

    public function test_appends_to_clean_file(): void
    {
        file_put_contents($this->routesFile, "<?php\n\n");

        $result = $this->manager()->ensureResourceRoute($this->definition());
        $content = file_get_contents($this->routesFile);

        self::assertTrue($result->wasAdded());
        self::assertSame(RouteRegistrationResult::STATUS_ADDED, $result->status);
        self::assertStringStartsWith('<?php', $content);
        self::assertStringContainsString('// BEGIN auto-crud:products', $content);
    }

    public function test_preserves_existing_user_routes(): void
    {
        $userRoutes = "<?php\nRoute::get('/home', fn () => 'home');\n";
        file_put_contents($this->routesFile, $userRoutes);

        $this->manager()->ensureResourceRoute($this->definition());
        $content = file_get_contents($this->routesFile);

        self::assertStringStartsWith($userRoutes, $content);
        self::assertStringContainsString('// BEGIN auto-crud:products', $content);
    }

    public function test_replaces_existing_marker_block(): void
    {
        $prefix = "<?php\nRoute::get('/home', fn () => 'home');\n";
        $oldBlock = "// BEGIN auto-crud:products\nRoute::resource('products', \\App\\Http\\Controllers\\OldProductController::class);\n// END auto-crud:products\n";
        $suffix = "Route::get('/about', fn () => 'about');\n";
        file_put_contents($this->routesFile, $prefix.$oldBlock.$suffix);

        $result = $this->manager()->ensureResourceRoute($this->definition());
        $content = file_get_contents($this->routesFile);

        self::assertTrue($result->wasUpdated());
        self::assertSame(RouteRegistrationResult::STATUS_UPDATED, $result->status);
        self::assertStringStartsWith($prefix, $content);
        self::assertStringEndsWith($suffix, $content);
        self::assertStringNotContainsString('OldProductController', $content);
        self::assertStringContainsString("Route::resource('products', \\App\\Http\\Controllers\\ProductController::class)->except(['show']);", $content);
    }

    public function test_skips_when_manual_resource_route_exists(): void
    {
        $original = "<?php\nRoute::resource('products', ProductController::class);\n";
        file_put_contents($this->routesFile, $original);

        $result = $this->manager()->ensureResourceRoute($this->definition());

        self::assertTrue($result->wasSkipped());
        self::assertSame('manual route detected', $result->skipReason);
        self::assertSame($original, file_get_contents($this->routesFile));
    }

    public function test_skips_when_manual_named_route_exists(): void
    {
        $original = "<?php\nRoute::get('/products', fn () => null)->name('products.index');\n";
        file_put_contents($this->routesFile, $original);

        $result = $this->manager()->ensureResourceRoute($this->definition());

        self::assertTrue($result->wasSkipped());
        self::assertSame($original, file_get_contents($this->routesFile));
    }

    public function test_throws_when_file_not_found(): void
    {
        $missingPath = $this->directory.DIRECTORY_SEPARATOR.'missing.php';

        $this->expectException(RouteManagerException::class);
        $this->expectExceptionMessage('Routes file not found:');
        (new RouteManager($missingPath))->ensureResourceRoute($this->definition());
    }

    public function test_throws_when_file_not_writable(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('Windows does not reliably enforce chmod permissions.');
        }

        file_put_contents($this->routesFile, "<?php\n");
        chmod($this->routesFile, 0444);
        if (is_writable($this->routesFile)) {
            self::markTestSkipped('The current user can write despite chmod restrictions.');
        }

        $this->expectException(RouteManagerException::class);
        $this->expectExceptionMessage('Routes file is not writable:');
        $this->manager()->ensureResourceRoute($this->definition());
    }

    public function test_throws_on_malformed_marker(): void
    {
        file_put_contents($this->routesFile, "<?php\n// BEGIN auto-crud:products\n");

        $this->expectException(RouteManagerException::class);
        $this->expectExceptionMessage('Malformed auto-crud marker block');
        $this->manager()->ensureResourceRoute($this->definition());
    }

    public function test_throws_on_composite_primary_key(): void
    {
        $this->expectException(RouteManagerException::class);
        $this->expectExceptionMessage('composite primary key');
        $this->manager()->ensureResourceRoute($this->definition(['tenant', 'code']));
    }

    public function test_throws_on_missing_primary_key(): void
    {
        $this->expectException(RouteManagerException::class);
        $this->expectExceptionMessage('no primary key detected');
        $this->manager()->ensureResourceRoute($this->definition([]));
    }

    public function test_does_not_modify_file_when_skipped(): void
    {
        $original = "<?php\nRoute::resource(\"products\", ProductController::class);\n";
        file_put_contents($this->routesFile, $original);
        $beforeHash = hash_file('sha256', $this->routesFile);

        $this->manager()->ensureResourceRoute($this->definition());

        self::assertSame($beforeHash, hash_file('sha256', $this->routesFile));
    }

    public function test_atomic_write_leaves_no_temporary_file(): void
    {
        file_put_contents($this->routesFile, "<?php\n");

        $this->manager()->ensureResourceRoute($this->definition());

        self::assertSame([], glob($this->directory.DIRECTORY_SEPARATOR.'.routes-*'));
    }

    public function test_newline_handling_for_file_without_final_newline(): void
    {
        $original = '<?php';
        file_put_contents($this->routesFile, $original);

        $this->manager()->ensureResourceRoute($this->definition());
        $content = file_get_contents($this->routesFile);

        self::assertStringStartsWith("<?php\n// BEGIN auto-crud:products", $content);
    }

    public function test_inserts_before_closing_tag_and_preserves_it(): void
    {
        $original = "<?php\n?>\n";
        file_put_contents($this->routesFile, $original);

        $this->manager()->ensureResourceRoute($this->definition());
        $content = file_get_contents($this->routesFile);

        self::assertLessThan(strpos($content, '?>'), strpos($content, '// BEGIN auto-crud:products'));
        self::assertStringEndsWith("?>\n", $content);
    }

    private function manager(): RouteManager
    {
        return new RouteManager($this->routesFile);
    }

    /** @param list<string> $primaryKeys */
    private function definition(array $primaryKeys = ['id']): CrudDefinition
    {
        $columns = [];
        foreach ($primaryKeys as $primaryKey) {
            $columns[] = new ColumnDefinition($primaryKey, 'integer', false, null, false, true);
        }
        if ($primaryKeys === ['id']) {
            $columns[0] = new ColumnDefinition('id', 'integer', false, null, true, true);
        }

        return new CrudDefinition('products', $primaryKeys, $columns, []);
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

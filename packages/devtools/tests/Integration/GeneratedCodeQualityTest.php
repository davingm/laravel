<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use RuntimeException;

final class GeneratedCodeQualityTest extends IntegrationTestCase
{
    public function test_generated_model_is_valid_php(): void
    {
        $this->generateCrud();

        $this->assertPhpFileIsValid($this->generatedModelPath());
    }

    public function test_generated_controller_is_valid_php(): void
    {
        $this->generateCrud();

        $this->assertPhpFileIsValid($this->generatedControllerPath());
    }

    public function test_generated_views_compile(): void
    {
        $this->generateCrud();

        foreach (['index', 'create', 'edit'] as $view) {
            $contents = file_get_contents($this->generatedViewPath($view));
            self::assertNotFalse($contents);
            self::assertIsString(Blade::compileString($contents));
        }
    }

    public function test_generated_model_can_be_instantiated(): void
    {
        $this->generateCrud();

        self::assertInstanceOf(\App\Models\Product::class, new \App\Models\Product());
    }

    public function test_generated_controller_can_be_resolved(): void
    {
        $this->generateCrud();

        self::assertInstanceOf(\App\Http\Controllers\ProductController::class, $this->app->make(\App\Http\Controllers\ProductController::class));
    }

    private function generateCrud(): void
    {
        self::assertSame(0, Artisan::call('crud:generate', ['model' => 'Product']));
    }

    private function assertPhpFileIsValid(string $path): void
    {
        $disabledFunctions = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
        if (! function_exists('shell_exec') || in_array('shell_exec', $disabledFunctions, true)) {
            self::markTestSkipped('shell_exec is disabled; PHP syntax lint cannot be invoked.');
        }

        $command = escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path).' 2>&1';
        $output = shell_exec($command);

        if ($output === null) {
            throw new RuntimeException('Unable to run PHP syntax lint.');
        }

        self::assertStringContainsString('No syntax errors detected', $output, $output);
    }

    private function generatedModelPath(): string
    {
        return $this->appBasePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR.'Product.php';
    }

    private function generatedControllerPath(): string
    {
        return $this->appBasePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'ProductController.php';
    }

    private function generatedViewPath(string $view): string
    {
        return $this->appBasePath.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.$view.'.blade.php';
    }
}

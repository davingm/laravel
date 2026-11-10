<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\Artisan;

final class PathResolutionTest extends IntegrationTestCase
{
    public function test_model_written_to_app_models_path(): void
    {
        self::assertSame(0, Artisan::call('crud:generate', ['model' => 'Product']));

        self::assertFileExists($this->appBasePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR.'Product.php');
    }

    public function test_controller_written_to_app_http_controllers_path(): void
    {
        self::assertSame(0, Artisan::call('crud:generate', ['model' => 'Product']));

        self::assertFileExists($this->appBasePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'ProductController.php');
    }

    public function test_views_written_to_resources_views_path(): void
    {
        self::assertSame(0, Artisan::call('crud:generate', ['model' => 'Product']));

        $viewsPath = $this->appBasePath.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'products';

        foreach (['index', 'create', 'edit'] as $view) {
            self::assertFileExists($viewsPath.DIRECTORY_SEPARATOR.$view.'.blade.php');
        }
    }

    public function test_route_appended_to_web_php(): void
    {
        self::assertSame(0, Artisan::call('crud:generate', ['model' => 'Product']));

        $routesFile = $this->appBasePath.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php';

        self::assertStringContainsString('// BEGIN auto-crud:products', file_get_contents($routesFile));
    }
}

<?php

declare(strict_types=1);

namespace Davingm\LaravelRuntime\App\Providers;

use Davingm\LaravelRuntime\App\Commands\BuildCommand;
use Davingm\LaravelRuntime\App\Commands\FrontendGenerateCommand;
use Davingm\LaravelRuntime\App\Commands\PreviewCommand;
use Davingm\LaravelRuntime\App\Middleware\CliRequestTelemetryMiddleware;
use Davingm\LaravelRuntime\App\Middleware\MinifyHtmlMiddleware;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laravel\Generators\CrudGenerateCommand;
use Laravel\Generators\MakeAllCommand;
use Laravel\Generators\MakeControllerCommand;
use Laravel\Generators\MakeDashboardCommand;
use Laravel\Generators\MakeMigrationCommand;
use Laravel\Generators\MakePageCommand;
use Laravel\Generators\MakeRolesCommand;

final class FrameworkServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::directive('pageMeta', function (): string {
            return <<<'PHP'
                <?php if (! empty($frontendPayload['meta']['title'] ?? null)): ?><title><?= e($frontendPayload['meta']['title']) ?></title><?php endif; ?>
                <?php if (! empty($frontendPayload['meta']['description'] ?? null)): ?><meta name="description" content="<?= e($frontendPayload['meta']['description']) ?>"><?php endif; ?>
                PHP;
        });

        Blade::directive('payload', function (): string {
            return '<?php if (isset($frontendPayload)): ?><script type="application/json" data-page-payload><?= json_encode($frontendPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script><?php endif; ?>';
        });

        Blade::directive('navigate', function (string $expression): string {
            return "data-navigate=\"<?php echo e({$expression}); ?>\"";
        });

        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', MinifyHtmlMiddleware::class);
        $router->pushMiddlewareToGroup('web', CliRequestTelemetryMiddleware::class);
        $router->pushMiddlewareToGroup('api', CliRequestTelemetryMiddleware::class);

        $this->commands([
            BuildCommand::class,
            CrudGenerateCommand::class,
            FrontendGenerateCommand::class,
            MakeAllCommand::class,
            MakeControllerCommand::class,
            MakeDashboardCommand::class,
            MakeMigrationCommand::class,
            MakePageCommand::class,
            MakeRolesCommand::class,
            PreviewCommand::class,
        ]);

        $this->app->booted(function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }
}

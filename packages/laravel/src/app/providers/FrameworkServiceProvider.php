<?php

declare(strict_types=1);

namespace Davingm\LaravelRuntime\App\Providers;

use Davingm\LaravelRuntime\App\Commands\BuildCommand;
use Davingm\LaravelRuntime\App\Commands\FrontendGenerateCommand;
use Davingm\LaravelRuntime\App\Commands\PreviewCommand;
use Davingm\LaravelRuntime\App\Middleware\CliRequestTelemetryMiddleware;
use Davingm\LaravelRuntime\App\Middleware\MinifyHtmlMiddleware;
use Davingm\LaravelRuntime\Compiler\Runtime\SingleFilePageCompiler;
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
        Blade::getFacadeRoot()->prepareStringsForCompilationUsing(
            static fn (string $source): string => SingleFilePageCompiler::prepareBlade($source),
        );

        Blade::anonymousComponentPath(base_path('src/components'));

        foreach ([
            base_path('node_modules/@davingm/welcome/blade'),
            base_path('node_modules/davingm-welcome/blade'),
        ] as $welcomeBladePath) {
            if (is_dir($welcomeBladePath)) {
                Blade::anonymousComponentPath($welcomeBladePath);
                break;
            }
        }

        Blade::directive('pageMeta', function (): string {
            return <<<'PHP'
                <title><?= e($frontendPayload['meta']['title'] ?? config('app.name', 'Laravel')) ?></title>
                <meta name="description" content="<?= e($frontendPayload['meta']['description'] ?? '') ?>">
                <meta property="og:title" content="<?= e($frontendPayload['meta']['ogTitle'] ?? $frontendPayload['meta']['title'] ?? '') ?>">
                <meta property="og:description" content="<?= e($frontendPayload['meta']['ogDescription'] ?? $frontendPayload['meta']['description'] ?? '') ?>">
                <meta property="og:image" content="<?= e($frontendPayload['meta']['ogImage'] ?? '') ?>">
                <meta property="og:url" content="<?= e($frontendPayload['meta']['ogUrl'] ?? request()->url()) ?>">
                <meta property="og:type" content="<?= e($frontendPayload['meta']['ogType'] ?? 'website') ?>">
                <meta property="og:site_name" content="<?= e(config('app.name', 'Laravel')) ?>">
                <meta name="twitter:title" content="<?= e($frontendPayload['meta']['twitterTitle'] ?? $frontendPayload['meta']['ogTitle'] ?? $frontendPayload['meta']['title'] ?? '') ?>">
                <meta name="twitter:description" content="<?= e($frontendPayload['meta']['twitterDescription'] ?? $frontendPayload['meta']['ogDescription'] ?? $frontendPayload['meta']['description'] ?? '') ?>">
                <meta name="twitter:image" content="<?= e($frontendPayload['meta']['twitterImage'] ?? $frontendPayload['meta']['ogImage'] ?? '') ?>">
                <meta name="twitter:card" content="<?= e($frontendPayload['meta']['twitterCard'] ?? 'summary_large_image') ?>">
                <?php if (! empty($frontendPayload['meta']['articleAuthor'] ?? null)): ?><meta property="article:author" content="<?= e($frontendPayload['meta']['articleAuthor']) ?>"><?php endif; ?>
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

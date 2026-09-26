<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
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
    }
}

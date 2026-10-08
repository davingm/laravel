<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * File-based auto-router for pages/**.blade.php.
 *
 * Conventions (mirrors Nuxt file-system routing):
 *   pages/home.blade.php          →  GET /home
 *   pages/index.blade.php         →  GET /          (root index)
 *   pages/about/index.blade.php   →  GET /about
 *   pages/about/us-me.blade.php   →  GET /about/us-me
 *   pages/blog/[slug].blade.php   →  GET /blog/{slug}  (dynamic segment)
 *
 * Route names follow the dot-notation of the view key:
 *   pages/about/us-me.blade.php   →  name: pages.about.us-me
 *   pages/about/index.blade.php   →  name: pages.about
 *
 * Exclude behaviour:
 *   'admin'   → exclude ONLY the exact /admin route, NOT sub-pages
 *   'admin/*' → exclude /admin and ALL sub-pages under it
 */
class PageRouter
{
    /**
     * Scan resources/views/pages and register GET routes for every Blade page.
     * Call this once from routes/web.php or a ServiceProvider.
     *
     * @param  array{
     *   prefix?: string,
     *   middleware?: string|string[],
     *   data?: array<string, mixed>,
     *   exclude?: string[],
     * }  $options
     */
    public static function register(array $options = []): void
    {
        $pagesPath = base_path('src/pages');

        if (! File::isDirectory($pagesPath)) {
            return;
        }

        $prefix = $options['prefix'] ?? '';
        $middleware = (array) ($options['middleware'] ?? ['web']);
        $extraData = $options['data'] ?? [];
        $excludedPages = $options['exclude'] ?? [];

        foreach (File::allFiles($pagesPath) as $file) {
            if ($file->getExtension() !== 'php' || Str::startsWith($file->getFilename(), '_')) {
                continue;
            }

            [$uri, $viewKey, $pageKey, $routeName] = self::resolve($file->getPathname(), $pagesPath, $prefix);

            if (self::isExcluded($pageKey, $uri, $excludedPages)) {
                continue;
            }

            Route::middleware($middleware)->get($uri, function () use ($viewKey, $pageKey, $extraData) {
                return Frontend::render($viewKey, $pageKey, $extraData);
            })->name($routeName);
        }
    }

    /**
     * Check whether a resolved page should be excluded from auto-registration.
     *
     * Exclude patterns:
     *   'about'    → exact match only: excludes pages.about but NOT pages.about.team
     *   'about/*'  → wildcard: excludes pages.about AND all sub-pages
     *   '/about'   → URI match (leading slash is normalised away)
     *
     * @param  string[]  $excludedPages
     */
    public static function isExcluded(string $pageKey, string $uri, array $excludedPages): bool
    {
        foreach ($excludedPages as $pattern) {
            // Wildcard pattern: 'admin/*' → exclude admin and all sub-pages
            if (Str::endsWith($pattern, '/*') || Str::endsWith($pattern, '*')) {
                $prefix = rtrim(str_replace(['/*', '*'], '', $pattern), '.');

                if ($pageKey === $prefix || Str::startsWith($pageKey, $prefix.'.')) {
                    return true;
                }

                continue;
            }

            // Exact pattern: 'admin' → ONLY exclude pages.admin (the index), not admin.users
            $normalised = ltrim($pattern, '/');
            if ($pageKey === $normalised || $uri === '/'.$normalised) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve a blade file path into [uri, viewKey, pageKey, routeName].
     *
     * - viewKey  = actual dot-notation Laravel view key  (e.g. "about.index", "home", "index")
     * - pageKey  = collapsed key for route names / payload (e.g. "about", "home", "index")
     *
     * @return array{string, string, string, string}
     */
    public static function resolve(string $absolutePath, string $pagesPath, string $prefix = ''): array
    {
        // Normalise both paths to forward slashes to handle Windows backslashes consistently
        $absolutePath = str_replace('\\', '/', $absolutePath);
        $pagesPath = rtrim(str_replace('\\', '/', $pagesPath), '/');

        // Relative path, e.g. "about/us-me.blade.php"
        $relative = ltrim(Str::after($absolutePath, $pagesPath), '/');

        // Strip .blade.php extension
        $relative = Str::beforeLast($relative, '.blade.php');

        // Segments: ['about', 'index'] or ['home'] or ['blog', '[slug]']
        $segments = explode('/', $relative);

        // viewKey: full dot-notation, no collapsing — this is what view() needs
        $viewKey = implode('.', $segments);

        // URI segments: convert [param] → {param}, drop trailing "index"
        $uriSegments = array_map(
            fn (string $s) => preg_match('/^\[(.+)\]$/', $s, $m) ? '{'.$m[1].'}' : $s,
            $segments
        );
        if (end($uriSegments) === 'index') {
            array_pop($uriSegments);
        }
        $uri = '/'.ltrim(implode('/', array_filter([$prefix, implode('/', $uriSegments)])), '/');

        // pageKey: collapsed — "index" only kept when it is the sole segment
        $keySegments = $segments;
        if (count($keySegments) > 1 && end($keySegments) === 'index') {
            array_pop($keySegments);
        }
        $pageKey = implode('.', $keySegments);

        // Route name: pages.<pageKey>, root stays "pages"
        $routeName = 'pages'.($pageKey !== 'index' ? '.'.$pageKey : '');

        return [$uri, $viewKey, $pageKey, $routeName];
    }
}

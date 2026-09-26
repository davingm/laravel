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
        $pagesPath = resource_path('views/pages');

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

            if (collect($excludedPages)->contains(
                fn (string $excludedPage): bool => $pageKey === $excludedPage || Str::startsWith($pageKey, $excludedPage.'.')
            )) {
                continue;
            }

            Route::middleware($middleware)->get($uri, function () use ($viewKey, $pageKey, $extraData) {
                return Frontend::render($viewKey, $pageKey, $extraData);
            })->name($routeName);
        }
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
        // e.g. "about/us-me.blade.php" or "about\us-me.blade.php"
        $relative = Str::after($absolutePath, $pagesPath.DIRECTORY_SEPARATOR);

        // Normalise to forward slashes and strip .blade.php
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
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

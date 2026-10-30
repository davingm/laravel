<?php

namespace Davingm\LaravelRuntime\App\Support;

use Davingm\LaravelRuntime\Compiler\Runtime\SingleFilePageCompiler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Frontend
{
    public static function render(string $viewKey, ?string $pageKey = null, array $data = [], ?string $layout = 'layouts.default'): View
    {
        $viewKey = Str::startsWith($viewKey, 'pages.') ? $viewKey : 'pages.'.$viewKey;
        $pageKey ??= Str::after($viewKey, 'pages.');
        $layout = self::resolveLayout($layout);

        $appName = config('app.name', 'Laravel');
        $pageTitle = $data['title'] ?? Str::headline(last(explode('.', $pageKey)) ?: $appName);
        $documentTitle = Str::endsWith($pageTitle, ' | '.$appName) ? $pageTitle : $pageTitle.' | '.$appName;
        $pageDescription = $data['description'] ?? 'Halaman '.$pageTitle.'.';
        $ogImage = $data['ogImage'] ?? asset('Laravel.svg');
        $payload = [
            'page' => $pageKey,
            'url' => request()->fullUrl(),
            'path' => request()->path(),
            'data' => $data,
            'meta' => [
                'title' => $documentTitle,
                'description' => $pageDescription,
                'ogTitle' => $data['ogTitle'] ?? $pageTitle,
                'ogDescription' => $data['ogDescription'] ?? $pageDescription,
                'ogImage' => $ogImage,
                'ogUrl' => $data['ogUrl'] ?? request()->url(),
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        $viewData = array_merge($data, [
            'frontendPage' => $pageKey,
            'frontendPayload' => $payload,
            'frontendLayout' => $layout,
        ]);
        $pageView = view($viewKey, $viewData);
        $pageSource = File::get($pageView->getPath());

        if (SingleFilePageCompiler::isSingleFilePage($pageSource)) {
            $pageContent = $pageView->render();
            $view = view('index', array_merge($viewData, ['pageContent' => $pageContent]));
        } else {
            $view = $pageView;
        }

        self::writePayload($pageKey, $payload);

        return $view->with('frontendPage', $pageKey)
            ->with('frontendPayload', $payload)
            ->with('frontendLayout', $layout);
    }

    private static function resolveLayout(?string $layout): string
    {
        $layout = trim($layout ?? 'default');
        $view = Str::startsWith($layout, 'layouts.') ? $layout : 'layouts.'.$layout;

        if ($view === 'layouts.app' && ! view()->exists($view) && view()->exists('layouts.default')) {
            return 'layouts.default';
        }

        if (! view()->exists($view)) {
            throw new InvalidArgumentException("Layout [{$view}] does not exist. Add it under src/layouts/ or select a registered layout.");
        }

        return $view;
    }

    public static function payload(string $page): ?array
    {
        $path = self::payloadPath($page);

        return File::exists($path) ? json_decode(File::get($path), true) : null;
    }

    public static function writePayload(string $page, array $payload): void
    {
        $path = self::payloadPath($page);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function manifest(): array
    {
        $path = storage_path('../.laravel/cache/manifest.json');

        return File::exists($path) ? (json_decode(File::get($path), true) ?: []) : [];
    }

    public static function generateManifest(): array
    {
        $pagesPath = base_path('src/pages');
        $pages = [];

        if (File::isDirectory($pagesPath)) {
            foreach (File::allFiles($pagesPath) as $file) {
                if ($file->getExtension() !== 'php' || Str::startsWith($file->getFilename(), '_')) {
                    continue;
                }

                [, , $pageKey] = PageRouter::resolve($file->getPathname(), $pagesPath);
                $pages[$pageKey] = 'pages.'.$pageKey;
            }
        }

        $manifest = [
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'pages' => $pages,
        ];
        $path = storage_path('../.laravel/cache/manifest.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $manifest;
    }

    private static function payloadPath(string $page): string
    {
        $safeName = str_replace(['/', '\\'], '.', trim($page, '.'));

        return storage_path('../.laravel/cache/payloads/'.$safeName.'.json');
    }
}

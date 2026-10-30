<?php

namespace Davingm\LaravelRuntime\App\Support;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Frontend
{
    public static function render(string $viewKey, ?string $pageKey = null, array $data = [], ?string $layout = 'layouts.app'): View
    {
        $viewKey = Str::startsWith($viewKey, 'pages.') ? $viewKey : 'pages.'.$viewKey;
        $pageKey ??= Str::after($viewKey, 'pages.');

        $view = view($viewKey, $data);
        $payload = [
            'page' => $pageKey,
            'url' => request()->fullUrl(),
            'path' => request()->path(),
            'data' => $data,
            'meta' => Arr::only($data, ['title', 'description']),
            'generated_at' => now()->toIso8601String(),
        ];

        self::writePayload($pageKey, $payload);

        return $view->with('frontendPage', $pageKey)
            ->with('frontendPayload', $payload)
            ->with('frontendLayout', $layout);
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

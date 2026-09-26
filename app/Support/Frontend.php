<?php

namespace App\Support;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Frontend
{
    /**
     * Render a page view and build its frontend payload.
     *
     * @param  string  $viewKey  Full dot-notation view key, e.g. "about.index", "home"
     * @param  string|null  $pageKey  Collapsed key used for payload / cache naming.
     *                                Defaults to $viewKey when omitted.
     * @param  array<string, mixed>  $data
     */
    public static function render(string $viewKey, ?string $pageKey = null, array $data = [], ?string $layout = 'layouts.app'): View
    {
        // Normalise: strip leading "pages." if caller passed the full prefixed key
        $viewKey = Str::startsWith($viewKey, 'pages.') ? $viewKey : 'pages.'.$viewKey;

        // pageKey used for payload filename / route name — default to viewKey without prefix
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

        if (! File::exists($path)) {
            return null;
        }

        return json_decode(File::get($path), true);
    }

    public static function writePayload(string $page, array $payload): void
    {
        $path = self::payloadPath($page);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function manifest(): array
    {
        $path = storage_path('../.davingm/cache/manifest.json');

        if (! File::exists($path)) {
            return [];
        }

        return json_decode(File::get($path), true) ?: [];
    }

    public static function generateManifest(): array
    {
        $pagesPath = resource_path('views/pages');
        $pages = [];

        if (File::isDirectory($pagesPath)) {
            foreach (File::allFiles($pagesPath) as $file) {
                if ($file->getExtension() !== 'php' || Str::startsWith($file->getFilename(), '_')) {
                    continue;
                }

                [, $pageKey] = PageRouter::resolve($file->getPathname(), $pagesPath);
                $pages[$pageKey] = 'pages.'.$pageKey;
            }
        }

        $manifest = [
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'pages' => $pages,
        ];
        $path = storage_path('../.davingm/cache/manifest.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $manifest;
    }

    private static function payloadPath(string $page): string
    {
        $safeName = str_replace(['/', '\\'], '.', trim($page, '.'));

        return storage_path('../.davingm/cache/payloads/'.$safeName.'.json');
    }
}

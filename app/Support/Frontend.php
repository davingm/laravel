<?php

namespace App\Support;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Frontend
{
    public static function render(string $page, array $data = [], ?string $layout = 'layouts.app'): View
    {
        $view = view('pages.'.$page, $data);
        $payload = [
            'page' => $page,
            'url' => request()->fullUrl(),
            'path' => request()->path(),
            'data' => $data,
            'meta' => Arr::only($data, ['title', 'description']),
            'generated_at' => now()->toIso8601String(),
        ];

        self::writePayload($page, $payload);

        return $view->with('frontendPage', $page)
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
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $relative = Str::after($file->getPathname(), $pagesPath.DIRECTORY_SEPARATOR);
                $name = Str::beforeLast(str_replace(DIRECTORY_SEPARATOR, '.', $relative), '.blade.php');
                $pages[$name] = 'pages.'.$name;
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

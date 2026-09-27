<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePageCommand extends Command
{
    protected $signature = 'make:page
                            {name : Page path, e.g. "about" or "about/team"}
                            {--force : Overwrite existing page if it already exists}';

    protected $description = 'Create a new Blade page';

    public function handle(): int
    {
        $name = trim(str_replace('\\', '/', $this->argument('name')), '/');
        $targetPath = resource_path('views/pages/'.str_replace('.', '/', $name).'.blade.php');
        $routeCacheExists = File::exists(app()->getCachedRoutesPath());

        if (File::exists($targetPath) && ! $this->option('force')) {
            $this->line("  <fg=yellow>!</> Page already exists: <fg=cyan>{$targetPath}</> (use <fg=yellow>--force</> to overwrite)");

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($targetPath));
        File::put($targetPath, $this->stub($name));

        if ($routeCacheExists) {
            $this->warn('Route cache was preserved and may be stale. Run `php artisan route:clear` to load the new page, then `php artisan route:cache` to enable route caching again.');
        } else {
            Artisan::call('route:clear');
        }

        $uri = '/'.implode('/', array_map(
            fn (string $segment) => Str::kebab($segment),
            explode('/', $name),
        ));

        $this->newLine();
        $this->line("  <fg=green>✓</> Page created: <fg=cyan>resources/views/pages/{$name}.blade.php</>");
        $this->line("  <fg=gray>  Route registered automatically → {$uri}</>");
        $this->newLine();

        return self::SUCCESS;
    }

    private function stub(string $name): string
    {
        $headline = Str::headline(basename(str_replace('/', ' ', $name)));

        return <<<BLADE
@extends('layouts.app')

@php
\$seo = [
    'title' => '{$headline}',
    'description' => 'Halaman {$headline}.',
    'ogTitle' => '{$headline}',
    'ogDescription' => 'Halaman {$headline}.',
    'ogImage' => asset('images/og-image.jpg'),
];
@endphp

@section('seo')
    <x-seo-meta :seo="\$seo" />
@endsection

@section('content')
<section class="crud-shell">
    <h1 class="crud-title">{$headline}</h1>
</section>
@endsection
BLADE;
    }
}

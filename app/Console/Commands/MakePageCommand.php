<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePageCommand extends Command
{
    protected $signature = 'make:page {name : Page path, e.g. "about" or "about/team"}';

    protected $description = 'Create a new page in resources/views/pages';

    public function handle(): int
    {
        $name = $this->argument('name');

        // Normalise: strip leading/trailing slashes, lowercase
        $name = trim(str_replace('\\', '/', $name), '/');

        $targetPath = resource_path('views/pages/'.str_replace('.', '/', $name).'.blade.php');

        if (File::exists($targetPath)) {
            $this->line("  <fg=yellow>!</> Page already exists: <fg=cyan>{$targetPath}</>");

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($targetPath));
        File::put($targetPath, $this->stub($name));

        $uri = '/'.implode('/', array_map(
            fn (string $s) => Str::kebab($s),
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
        $title = Str::headline(basename(str_replace('/', ' ', $name)));

        return <<<BLADE
        @extends('layouts.app')

        @section('content')
        <div>
            <h1>{$title}</h1>
        </div>
        @endsection
        BLADE;
    }
}

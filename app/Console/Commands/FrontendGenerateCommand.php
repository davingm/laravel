<?php

namespace App\Console\Commands;

use App\Support\Frontend;
use Illuminate\Console\Command;

class FrontendGenerateCommand extends Command
{
    protected $signature = 'frontend:generate {--clear : Clear generated payloads before rebuilding}';

    protected $description = 'Generate the Blade frontend manifest and runtime cache';

    public function handle(): int
    {
        if ($this->option('clear')) {
            $this->callSilent('view:clear');
        }

        $manifest = Frontend::generateManifest();
        $this->info('Frontend manifest generated: '.count($manifest['pages']).' page(s).');
        $this->line('Runtime output: .davingm/cache (ignored by Git)');

        return self::SUCCESS;
    }
}

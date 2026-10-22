<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;

final class DevCommand extends Command
{
    protected $signature = 'dev {--no-tui : Run without the terminal dashboard}';
    protected $description = 'Start the development dashboard, Laravel server, queue worker, and Vite';

    public function handle(): int
    {
        $node = getenv('NODE_BINARY') ?: 'node';
        $cli = base_path('.laravel/cli.js');
        if (!is_file($cli)) {
            $this->error('Devtools CLI is missing. Reinstall the Composer package.');
            return self::FAILURE;
        }

        $command = [$node, $cli, 'dev'];
        if ($this->option('no-tui')) $command[] = '--no-tui';
        $pipes = [];
        $process = @proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, base_path());
        if (!is_resource($process)) {
            $this->error("Unable to start Node.js using '{$node}'. Set NODE_BINARY if its executable has another name.");
            return self::FAILURE;
        }

        return proc_close($process);
    }
}

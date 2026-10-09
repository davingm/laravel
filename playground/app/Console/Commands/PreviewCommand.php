<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class PreviewCommand extends Command
{
    protected $signature = 'preview
        {--port=8000 : Port to serve on}
        {--build     : Run artisan build before starting preview}';

    protected $description = 'Serve the application in production preview mode (no .env changes)';

    public function handle(): int
    {
        $port = (int) $this->option('port');

        // Optionally run build first
        if ($this->option('build')) {
            $this->line('');
            $this->line('  <fg=gray>Running build first…</>');
            $exitCode = $this->call('build');
            if ($exitCode !== 0) {
                return self::FAILURE;
            }
        }

        $this->printBanner($port);

        // Write a temporary preview flag file that MinifyHtmlMiddleware reads.
        // We write the current PID so middleware can verify the process is alive.
        $flagFile = base_path('.auto/.preview');
        file_put_contents($flagFile, (string) getmypid());

        register_shutdown_function(static function () use ($flagFile): void {
            @unlink($flagFile);
        });

        $process = new Process(
            [PHP_BINARY, 'artisan', 'serve', '--port='.$port],
            base_path(),
            [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'AUTO_PREVIEW' => '1',
            ],
        );

        $process->setTimeout(null);
        $process->setTty(false);

        $process->start(function (string $type, string $buffer): void {
            $lines = explode("\n", trim($buffer));
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if (str_contains($line, 'Server running on')) {
                    continue;
                }
                if (str_contains($line, 'Press Ctrl')) {
                    continue;
                }
                $this->line('  <fg=gray>'.$line.'</>');
            }
        });

        // Handle Ctrl+C on Windows
        if (function_exists('sapi_windows_set_ctrl_handler')) {
            sapi_windows_set_ctrl_handler(function () use ($process, $flagFile): void {
                $process->stop();
                @unlink($flagFile);
                exit(0);
            });
        }

        // Handle Ctrl+C on POSIX
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () use ($process, $flagFile): void {
                $process->stop();
                @unlink($flagFile);
                $this->newLine();
                $this->line('  <fg=gray>Preview stopped.</>');
                $this->newLine();
                exit(0);
            });
        }

        try {
            while ($process->isRunning()) {
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
                usleep(100_000);
            }
        } finally {
            // Clean up flag file when process ends
            @unlink($flagFile);
        }

        return $process->getExitCode() ?? self::SUCCESS;
    }

    private function printBanner(int $port): void
    {
        $this->newLine();
        $this->line('  <fg=red;options=bold>▲ Preview Mode</> <fg=gray>(production, no .env changes)</>');
        $this->newLine();
        $this->line("  <fg=gray>Local:</> <fg=cyan>http://localhost:{$port}</>");
        $this->line('  <fg=gray>  APP_ENV  = production</>');
        $this->line('  <fg=gray>  APP_DEBUG = false</>');
        $this->line('  <fg=gray>  HTML minification = on</>');
        $this->newLine();
        $this->line('  <fg=gray>Press Ctrl+C to stop.</>');
        $this->newLine();
    }
}

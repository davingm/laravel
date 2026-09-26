<?php

namespace App\Console\Commands;

use App\Support\Frontend;
use Illuminate\Console\Command;

class BuildCommand extends Command
{
    protected $signature = 'build
        {--skip-tests : Skip running the test suite}
        {--skip-npm   : Skip npm run build (frontend asset compilation)}';

    protected $description = 'Build and prepare the application for production';

    /** Steps with their labels for display */
    private array $steps = [
        'tests' => 'Running tests',
        'npm' => 'Building frontend assets',
        'config' => 'Caching config',
        'routes' => 'Caching routes',
        'views' => 'Caching views',
        'events' => 'Caching events',
        'frontend' => 'Generating frontend manifest',
    ];

    public function handle(): int
    {
        $startMs = (int) (microtime(true) * 1000);

        $this->newLine();
        $this->line('  <fg=red;options=bold>Building for production…</>');
        $this->newLine();

        // ── 1. Tests ──────────────────────────────────────────────────────────
        if (! $this->option('skip-tests')) {
            $this->step('tests');
            $exitCode = $this->runTests();

            if ($exitCode !== 0) {
                $this->printError('Tests failed — build aborted. Run with --skip-tests to bypass.');

                return self::FAILURE;
            }

            $this->stepDone('tests');
        } else {
            $this->stepSkipped('tests');
        }

        // ── 2. npm run build ──────────────────────────────────────────────────
        if (! $this->option('skip-npm')) {
            $this->step('npm');
            $exitCode = $this->runNpm();

            if ($exitCode !== 0) {
                $this->printError('npm run build failed — build aborted.');

                return self::FAILURE;
            }

            $this->stepDone('npm');
        } else {
            $this->stepSkipped('npm');
        }

        // ── 3. Clear stale caches ─────────────────────────────────────────────
        $this->callSilent('view:clear');
        $this->callSilent('cache:clear');
        $this->callSilent('config:clear');
        $this->callSilent('route:clear');
        $this->callSilent('event:clear');

        // ── 4. Rebuild caches ─────────────────────────────────────────────────
        $this->step('config');
        $this->callSilent('config:cache');
        $this->stepDone('config');

        $this->step('routes');
        $this->callSilent('route:cache');
        $this->stepDone('routes');

        $this->step('views');
        $this->callSilent('view:cache');
        $this->stepDone('views');

        $this->step('events');
        $this->callSilent('event:cache');
        $this->stepDone('events');

        // ── 5. Frontend manifest ──────────────────────────────────────────────
        $this->step('frontend');
        $manifest = Frontend::generateManifest();
        $this->stepDone('frontend', count($manifest['pages']).' page(s)');

        // ── Done ──────────────────────────────────────────────────────────────
        $elapsed = round((microtime(true) * 1000 - $startMs) / 1000, 1);
        $this->newLine();
        $this->line("  <fg=green;options=bold>✓</> Build complete <fg=gray>({$elapsed}s)</>");
        $this->newLine();

        return self::SUCCESS;
    }

    private function step(string $key): void
    {
        $label = $this->steps[$key];
        $this->line("  <fg=gray>…</> {$label}");
    }

    private function stepDone(string $key, string $extra = ''): void
    {
        $label = $this->steps[$key];
        $suffix = $extra !== '' ? " <fg=gray>{$extra}</>" : '';
        // Move cursor up one line and overwrite
        $this->output->write("\x1b[1A\r");
        $this->line("  <fg=green>✓</> {$label}{$suffix}");
    }

    private function stepSkipped(string $key): void
    {
        $label = $this->steps[$key];
        $this->line("  <fg=gray>–</> {$label} <fg=gray>skipped</>");
    }

    private function printError(string $message): void
    {
        $this->newLine();
        $this->line("  <fg=red>✗</> {$message}");
        $this->newLine();
    }

    private function runTests(): int
    {
        // Clear config before running tests to avoid stale cache interference
        $this->callSilent('config:clear');

        $process = proc_open(
            [PHP_BINARY, 'artisan', 'test', '--compact'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path(),
        );

        if (! is_resource($process)) {
            return 1;
        }

        while (! feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line !== false && trim($line) !== '') {
                $this->line('     '.$line, null, 'v');
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }

    private function runNpm(): int
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';
        $npm = $isWindows ? 'npm.cmd' : 'npm';

        // On Windows, proc_open with array descriptor requires shell invocation
        $cmd = $isWindows
            ? 'cmd /c npm run build 2>&1'
            : [$npm, 'run', 'build'];

        $process = proc_open(
            $cmd,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path(),
        );

        if (! is_resource($process)) {
            return 1;
        }

        while (! feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line !== false && trim($line) !== '') {
                $this->line('     '.rtrim($line), null, 'v');
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }
}

<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class DevtoolsInstallCommand extends Command
{
    protected $signature = 'devtools:install {--preset= : Preset to install (peminjaman)}';

    protected $description = 'Install a Laravel Devtools starter preset';

    public function handle(): int
    {
        $preset = strtolower(trim((string) $this->option('preset')));
        if ($preset !== 'peminjaman') {
            $this->error('Preset tidak dikenal. Preset tersedia: peminjaman.');
            return self::FAILURE;
        }

        $this->line('');
        $this->line(' Installing preset: peminjaman');
        $this->line('');
        $this->step(1, 8, 'Refresh the auth starter');
        if ($this->call('auth:install', ['--defer-database' => true]) !== self::SUCCESS) {
            $this->error('Auth starter gagal diperbarui; preset peminjaman belum dipasang.');
            return self::FAILURE;
        }
        $this->done('Auth starter files are ready');

        $this->step(2, 8, 'Add application models and controllers');
        $this->copyTree($this->stubPath('app/Http/Controllers'), app_path('Http/Controllers'), 'app/Http/Controllers');
        $this->copyTree($this->stubPath('app/Models'), app_path('Models'), 'app/Models');
        $this->done('Peminjaman application classes copied');

        $this->step(3, 8, 'Add database migrations');
        $this->publishMigrations();

        $this->step(4, 8, 'Copy dashboard pages, UI components, and native CSS');
        $this->copyTree($this->stubPath('resources/views/pages'), resource_path('views/pages'), 'resources/views/pages');
        $this->copyTree($this->stubPath('resources/views/components/ui'), resource_path('views/components/ui'), 'resources/views/components/ui');
        $this->publishConfig();
        $this->publishStylesheet();
        $this->publishScript();
        $this->publishMiddleware();
        $this->file('public/css/peminjaman.css');
        $this->file('public/js/peminjaman.js');
        $this->done('Preset pages use local CSS and do not require Vite or npm');

        $this->step(5, 8, 'Register preset routes and role middleware');
        $this->publishRoutes();
        $this->call('route:clear');
        $this->registerUserRoleAlias();
        $this->publishSeeder();
        $this->done('Routes use the existing administrator, operator, and user roles');

        $this->step(6, 8, 'Run migrations');
        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            $this->error('Migration gagal. File sudah dibuat; perbaiki koneksi database lalu jalankan php artisan migrate.');
            return self::FAILURE;
        }

        $this->step(7, 8, 'Seed sample tools and categories');
        if ($this->call('db:seed', ['--class' => 'AuthSeeder', '--force' => true]) !== self::SUCCESS) {
            $this->error('Seeder auth gagal. Jalankan php artisan db:seed --class=AuthSeeder setelah memperbaiki database.');
            return self::FAILURE;
        }
        if ($this->call('db:seed', ['--class' => 'PeminjamanDemoSeeder', '--force' => true]) !== self::SUCCESS) {
            $this->error('Seeder gagal. Jalankan php artisan db:seed --class=PeminjamanDemoSeeder setelah memperbaiki database.');
            return self::FAILURE;
        }
        $this->done('Sample categories and tools are ready');

        $this->step(8, 8, 'Run preset smoke checks');
        if (! $this->verifyInstallation()) {
            $this->error('Preset terpasang, tetapi pemeriksaan menemukan masalah. Perbaiki pesan di atas sebelum menggunakan fitur peminjaman.');
            return self::FAILURE;
        }

        $this->line('');
        $this->info('Preset peminjaman berhasil dipasang.');
        $this->line('Dashboard utama tersedia di /dashboard.');
        $this->line('');

        return self::SUCCESS;
    }

    private function verifyInstallation(): bool
    {
        $routeFile = base_path('routes/peminjaman.php');
        $webRoutes = base_path('routes/web.php');
        $routeSource = File::exists($routeFile) ? File::get($routeFile) : '';
        $webRouteSource = File::exists($webRoutes) ? File::get($webRoutes) : '';
        $routeFileIsLoaded = str_contains($webRouteSource, "require __DIR__.'/peminjaman.php';");

        $checks = [
            'Tabel peminjaman tersedia' => fn (): bool => collect([
                'kategoris', 'alats', 'peminjamans', 'peminjaman_items', 'activity_logs',
            ])->every(fn (string $table): bool => Schema::hasTable($table)),
            'Route pengajuan tercantum dan dimuat' => fn (): bool => $routeFileIsLoaded
                && str_contains($routeSource, "->name('peminjamans.request')"),
            'Route persetujuan operator tercantum dan dimuat' => fn (): bool => $routeFileIsLoaded
                && str_contains($routeSource, "->name('peminjamans.approve')"),
            'Route aktivitas dan laporan tercantum' => fn (): bool => $routeFileIsLoaded
                && str_contains($routeSource, "->name('activity.index')")
                && str_contains($routeSource, "->name('reports.index')")
                && str_contains($routeSource, "->name('reports.export')"),
            'CSS native dan komponen UI tersedia' => fn (): bool => File::exists(public_path('css/peminjaman.css'))
                && File::exists(resource_path('views/components/ui/input.blade.php'))
                && File::exists(resource_path('views/components/ui/button.blade.php')),
        ];

        $passed = true;
        foreach ($checks as $label => $check) {
            $ok = $check();
            $passed = $ok && $passed;
            $this->line(sprintf('  <fg=cyan>CHECK</> %-48s %s', $label, $ok ? '<fg=green>PASS</>' : '<fg=red>FAIL</>'));
        }

        $this->line('  <fg=cyan>CHECK</> Relasi item → tabel peminjaman                    <fg=yellow>RUNNING</>');
        try {
            $user = \App\Models\User::query()->firstOrFail();
            $alat = \App\Models\Alat::query()->firstOrFail();

            DB::beginTransaction();
            try {
                $loan = \App\Models\Peminjaman::query()->create([
                    'user_id' => $user->id,
                    'status' => 'menunggu',
                ]);
                $loan->items()->create(['alat_id' => $alat->id, 'jumlah' => 1]);
                DB::rollBack();
                $this->line('  <fg=green>PASS</> Foreign key menerima item dan transaksi uji di-rollback');
            } catch (\Throwable $exception) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                throw $exception;
            }
        } catch (\Throwable $exception) {
            $passed = false;
            $this->line('  <fg=red>FAIL</> Foreign key item: '.$exception->getMessage());
        }

        return $passed;
    }

    private function stubPath(string $relative = ''): string
    {
        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'presets'.DIRECTORY_SEPARATOR.'peminjaman';
        return $relative === '' ? $path : $path.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function step(int $number, int $total, string $label): void
    {
        $this->line('');
        $this->line("  {$number}/{$total}  {$label}");
    }

    private function done(string $message): void
    {
        $this->line("  ✓ {$message}");
    }

    private function file(string $path): void
    {
        $this->line("    ├─ + {$path}");
    }

    private function copyTree(string $source, string $destination, string $displayRoot): void
    {
        File::ensureDirectoryExists($destination);
        foreach (File::allFiles($source) as $file) {
            $relative = $file->getRelativePathname();
            $target = $destination.DIRECTORY_SEPARATOR.$relative;
            File::ensureDirectoryExists(dirname($target));
            File::copy($file->getPathname(), $target);
            $this->file($displayRoot.'/'.$relative);
        }
    }

    private function publishMigrations(): void
    {
        $migrations = [
            'create_kategoris_table.php.stub',
            'create_alats_table.php.stub',
            'add_stok_to_alats_table.php.stub',
            'create_peminjamans_table.php.stub',
            'create_peminjaman_items_table.php.stub',
            'create_activity_logs_table.php.stub',
        ];
        File::ensureDirectoryExists(database_path('migrations'));
        $base = now();
        $newMigrationIndex = 0;
        foreach ($migrations as $stub) {
            $suffix = Str::before($stub, '.php.stub');
            $existing = glob(database_path("migrations/*_{$suffix}.php")) ?: [];
            sort($existing);
            if ($existing !== []) {
                foreach ($existing as $target) {
                    File::copy($this->stubPath('database/migrations/'.$stub), $target);
                    $this->file('database/migrations/'.basename($target));
                }

                continue;
            } else {
                $stamp = $base->copy()->addSeconds($newMigrationIndex++);
                $target = database_path('migrations/'.$stamp->format('Y_m_d_His')."_{$suffix}.php");
                while (File::exists($target)) {
                    $stamp->addSecond();
                    $target = database_path('migrations/'.$stamp->format('Y_m_d_His')."_{$suffix}.php");
                }
            }
            File::ensureDirectoryExists(dirname($target));
            File::copy($this->stubPath('database/migrations/'.$stub), $target);
            $this->file('database/migrations/'.basename($target));
        }
    }

    private function publishStylesheet(): void
    {
        $css = File::get($this->stubPath('resources/css/peminjaman.css.stub'));
        $css = preg_replace('/^@(?:import|source)\s+.*;\s*$/m', '', $css) ?? $css;
        $css = "/* Native status styles for the borrowing pages. */\n".$css;

        $target = public_path('css/peminjaman.css');
        File::ensureDirectoryExists(dirname($target));
        File::put($target, $css);
    }

    private function publishScript(): void
    {
        $target = public_path('js/peminjaman.js');
        File::ensureDirectoryExists(dirname($target));
        File::copy($this->stubPath('resources/js/peminjaman.js.stub'), $target);
    }

    private function publishMiddleware(): void
    {
        $target = app_path('Http/Middleware/EnsureUserIsUserMiddleware.php');
        File::ensureDirectoryExists(dirname($target));
        File::copy($this->stubPath('app/Http/Middleware/EnsureUserIsUserMiddleware.php.stub'), $target);
        $this->file('app/Http/Middleware/EnsureUserIsUserMiddleware.php');
    }

    private function publishSeeder(): void
    {
        $target = database_path('seeders/PeminjamanDemoSeeder.php');
        File::ensureDirectoryExists(dirname($target));
        File::copy($this->stubPath('database/seeders/PeminjamanDemoSeeder.php.stub'), $target);
        $this->file('database/seeders/PeminjamanDemoSeeder.php');
    }

    private function publishRoutes(): void
    {
        $routeFile = base_path('routes/peminjaman.php');
        File::ensureDirectoryExists(dirname($routeFile));
        File::copy($this->stubPath('routes/web.php.stub'), $routeFile);
        $this->file('routes/peminjaman.php');

        $webRoutes = base_path('routes/web.php');
        File::ensureDirectoryExists(dirname($webRoutes));
        if (! File::exists($webRoutes)) {
            File::put($webRoutes, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        }
        $content = File::get($webRoutes);
        $require = "require __DIR__.'/peminjaman.php';";
        if (! str_contains($content, $require)) {
            File::append($webRoutes, "\n\n// preset:peminjaman\n{$require}\n");
        }
        $this->file('routes/web.php');
    }

    private function publishConfig(): void
    {
        $target = config_path('peminjaman.php');
        File::ensureDirectoryExists(dirname($target));
        File::copy($this->stubPath('config/peminjaman.php.stub'), $target);
        $this->file('config/peminjaman.php');
    }

    private function registerUserRoleAlias(): void
    {
        $bootstrap = base_path('bootstrap/app.php');
        $content = File::get($bootstrap);
        if (preg_match('/[\'"]role\.user[\'"]\s*=>/', $content)) {
            return;
        }

        $needle = '->alias([';
        $replacement = $needle."\n            'role.user' => \\App\\Http\\Middleware\\EnsureUserIsUserMiddleware::class,";
        File::put($bootstrap, str_replace($needle, $replacement, $content, $count));
    }
}

<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

/**
 * Scaffolds a complete auth starter (login, register, dashboard, roles)
 * onto a clean Laravel 12 project.
 *
 * Guards:
 *  1. Package devtools must be present (always true when this runs).
 *  2. Project must be "clean" — no existing auth controllers, no non-welcome views,
 *     no existing role columns in the users table (migration already applied).
 */
final class AuthInstallCommand extends Command
{
    protected $signature = 'auth:install {--force : Skip cleanliness check and overwrite existing files}';
    protected $description = 'Install auth UI: login, register, dashboard, and 3 role-based users (administrator, operator, user)';

    // ── ANSI helpers ────────────────────────────────────────────────────────────

    private function dim(string $text): string
    {
        return "\e[2m{$text}\e[0m";
    }

    private function green(string $text): string
    {
        return "\e[32m{$text}\e[0m";
    }

    private function blue(string $text): string
    {
        return "\e[34m{$text}\e[0m";
    }

    private function gray(string $text): string
    {
        return "\e[90m{$text}\e[0m";
    }

    private function bold(string $text): string
    {
        return "\e[1m{$text}\e[0m";
    }

    // ── Printers ─────────────────────────────────────────────────────────────────

    /** Print a step header like: ○ 1/8  Initialize project */
    private function step(int $n, int $total, string $label): void
    {
        $this->line('');
        $this->line(" {$this->green('○')} {$this->dim("{$n}/{$total}")}  {$this->bold($label)}");
    }

    /**
     * Print a single created file with a tree-style prefix.
     * Last item in a group uses └─, others use ├─.
     */
    private function file(string $relative, bool $last = false): void
    {
        $prefix = $last ? '   └─' : '   ├─';
        $this->line("  {$this->gray($prefix)} {$this->green('+')} {$relative}");
    }

    private function skip(string $relative): void
    {
        $this->line("  {$this->gray('   ├─')} {$this->dim('~')} {$this->gray("{$relative} (skipped)")}");
    }

    private function done(string $msg): void
    {
        $this->line("  {$this->green('✓')} {$msg}");
    }

    // ── Entry ────────────────────────────────────────────────────────────────────

    public function handle(): int
    {
        $this->line('');
        $this->line(" {$this->bold('artisan auth:install')} {$this->dim('— Laravel Auth Starter')}");
        $this->line('');

        $force = (bool) $this->option('force');

        // ── Guard 1: cleanliness check ─────────────────────────────────────────
        if (! $force && ! $this->isProjectClean()) {
            return self::FAILURE;
        }

        $total = 8;

        // ── Step 1: Check environment ──────────────────────────────────────────
        $this->step(1, $total, 'Initialize project');
        $this->line("  {$this->gray('  Checking Laravel 12 environment...')}");
        $this->checkEnvironment();
        $this->done('Laravel 12 environment ready');

        // ── Step 2: Publish migration ──────────────────────────────────────────
        $this->step(2, $total, 'Add migration');
        $this->updateUserModel();
        $migrationFile = $this->publishMigration($force);
        $this->file("database/migrations/{$migrationFile}");
        $this->done('Migration for role column created');

        // ── Step 3: Run migration ──────────────────────────────────────────────
        $this->step(3, $total, 'Run migration');
        if (! $this->runMigration()) {
            $this->error('Auth installation stopped because the role migration did not complete. Fix the database connection, then run php artisan migrate.');

            return self::FAILURE;
        }

        // ── Step 4: Add seeder ─────────────────────────────────────────────────
        $this->step(4, $total, 'Add seeder');
        $seederResult = $this->publishSeeder($force);
        if ($seederResult === false) {
            $this->error('Auth installation stopped because the default users could not be seeded. Fix the database, then run php artisan db:seed --class=AuthSeeder.');

            return self::FAILURE;
        }
        if ($seederResult === 'created') {
            $this->file('database/seeders/AuthSeeder.php');
        } else {
            $this->skip('database/seeders/AuthSeeder.php');
        }
        $this->done('3 default users seeded (administrator, operator, user)');

        // ── Step 5: Add controllers ────────────────────────────────────────────
        $this->step(5, $total, 'Add controllers');
        $controllers = $this->publishControllers($force);
        $count = count($controllers);
        foreach ($controllers as $i => $ctrl) {
            $this->file($ctrl, $i === $count - 1);
        }
        $this->done(count($controllers).' controllers created');

        // ── Step 6: Add middleware + bootstrap ────────────────────────────────
        $this->step(6, $total, 'Register role middleware');
        $this->publishMiddleware($force);
        $this->registerMiddlewareAliases();
        $this->done('Middleware registered: role.administrator, role.operator');

        // ── Step 7: Publish views + CSS/JS ────────────────────────────────────
        $this->step(7, $total, 'Publish views & assets');
        $views = $this->publishViews($force);
        foreach ($views as $i => $view) {
            $this->file($view, $i === count($views) - 1);
        }
        $this->publishAssets($force);
        $this->file('resources/css/app.css', false);
        $this->file('resources/js/app.js', false);
        $this->file('public/css/devtools-auth.css', false);
        $this->file('public/js/devtools-auth.js', false);
        $icons = $this->publishIcons($force);
        foreach ($icons as $i => $icon) {
            $this->file($icon, $i === count($icons) - 1);
        }
        $this->done('Views and assets published');

        // ── Step 8: Register routes ────────────────────────────────────────────
        $this->step(8, $total, 'Register routes');
        $this->registerRoutes();
        $this->done('Auth routes registered in routes/web.php');

        // ── Summary ────────────────────────────────────────────────────────────
        $this->line('');
        $this->line(" {$this->green('●')} {$this->bold('Auth installed successfully!')}");
        $this->line('');
        $this->line("  {$this->dim('Default credentials (change before deploying):')}");
        $this->line("  {$this->blue('admin@example.com')}    password  {$this->gray('→')}  administrator");
        $this->line("  {$this->blue('operator@example.com')} password  {$this->gray('→')}  operator");
        $this->line("  {$this->blue('user@example.com')}     password  {$this->gray('→')}  user");
        $this->line('');
        $this->line("  {$this->dim('Frontend assets:')}      served directly; npm is not required for auth UI");
        $this->line("  {$this->dim('Start dev server:')}  php artisan dev");
        $this->line("  {$this->dim('Open browser:')}      http://localhost:8000/login");
        $this->line('');

        return self::SUCCESS;
    }

    // ── Guards ───────────────────────────────────────────────────────────────────

    private function isProjectClean(): bool
    {
        $problems = [];

        // No existing auth controllers
        if (is_dir(app_path('Http/Controllers/Auth'))) {
            $problems[] = 'app/Http/Controllers/Auth/ already exists';
        }

        // No existing dashboard view
        if (File::exists(resource_path('views/pages/dashboard'))) {
            $problems[] = 'resources/views/pages/dashboard/ already exists';
        }

        // No existing auth views
        if (File::exists(resource_path('views/auth'))) {
            $problems[] = 'resources/views/auth/ already exists';
        }

        // No existing dashboard layout
        if (File::exists(resource_path('views/layouts/dashboard.blade.php'))) {
            $problems[] = 'resources/views/layouts/dashboard.blade.php already exists';
        }

        // Migration for role column already exists
        $migrations = glob(database_path('migrations/*_add_role_to_users_table.php'));
        if ($migrations !== [] && $migrations !== false) {
            $problems[] = 'Role migration already exists';
        }

        if ($problems !== []) {
            $this->line('');
            $this->error('Project does not look clean. auth:install aborted.');
            foreach ($problems as $problem) {
                $this->line("  {$this->gray('→')} {$problem}");
            }
            $this->line('');
            $this->line("  Run with {$this->blue('--force')} to overwrite existing files.");
            $this->line('');
            return false;
        }

        return true;
    }

    private function checkEnvironment(): void
    {
        // Verify Laravel 12 (framework ^12)
        $composerJson = base_path('composer.json');
        if (File::exists($composerJson)) {
            $manifest = json_decode(File::get($composerJson), true);
            $framework = $manifest['require']['laravel/framework'] ?? $manifest['require-dev']['laravel/framework'] ?? '';
            if ($framework !== '' && ! Str::contains($framework, ['12', '*'])) {
                $this->warn("Detected framework requirement: {$framework}. This command is designed for Laravel 12.");
            }
        }
    }

    // ── Migration ────────────────────────────────────────────────────────────────

    private function publishMigration(bool $force): string
    {
        $timestamp = now()->format('Y_m_d_His');
        $filename = "{$timestamp}_add_role_to_users_table.php";
        $destination = database_path("migrations/{$filename}");

        if (File::exists($destination) && ! $force) {
            return basename($destination);
        }

        File::ensureDirectoryExists(database_path('migrations'));
        File::put($destination, $this->stub('migration.php.stub'));

        return $filename;
    }

    private function runMigration(): bool
    {
        try {
            if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
                $this->warn('Migration failed. Run php artisan migrate manually after fixing the database connection.');

                return false;
            }

            $this->done('Migration executed');

            return true;
        } catch (Throwable $e) {
            $this->warn("Migration failed: {$e->getMessage()}. Run php artisan migrate manually.");

            return false;
        }
    }

    // ── Seeder ───────────────────────────────────────────────────────────────────

    private function publishSeeder(bool $force): string|false
    {
        $path = database_path('seeders/AuthSeeder.php');

        if (File::exists($path) && ! $force) {
            return $this->runSeeder() ? 'skipped' : false;
        }

        File::ensureDirectoryExists(database_path('seeders'));
        File::put($path, $this->stub('seeder.php.stub'));

        return $this->runSeeder() ? 'created' : false;
    }

    private function runSeeder(): bool
    {
        try {
            if ($this->call('db:seed', ['--class' => 'AuthSeeder', '--force' => true]) !== self::SUCCESS) {
                $this->warn('Seeder failed. Run php artisan db:seed --class=AuthSeeder manually after fixing the database.');

                return false;
            }

            $this->done('Seeder executed');

            return true;
        } catch (Throwable $e) {
            $this->warn("Seeder failed: {$e->getMessage()}. Run php artisan db:seed --class=AuthSeeder manually.");

            return false;
        }
    }

    // ── Controllers ──────────────────────────────────────────────────────────────

    /** @return list<string> */
    private function publishControllers(bool $force): array
    {
        $created = [];

        $map = [
            'Auth/LoginController.php'    => 'LoginController.php.stub',
            'Auth/RegisterController.php' => 'RegisterController.php.stub',
            'DashboardController.php'     => 'DashboardController.php.stub',
            'ProfileController.php'       => 'ProfileController.php.stub',
            'UserController.php'          => 'UserController.php.stub',
        ];

        foreach ($map as $relative => $stub) {
            $path = app_path("Http/Controllers/{$relative}");
            if (File::exists($path) && ! $force) {
                continue;
            }
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $this->stub($stub));
            $created[] = "app/Http/Controllers/{$relative}";
        }

        return $created;
    }

    // ── Middleware ────────────────────────────────────────────────────────────────

    private function publishMiddleware(bool $force): void
    {
        $map = [
            'EnsureUserIsAdministratorMiddleware.php' => 'EnsureUserIsAdministratorMiddleware.php.stub',
            'EnsureUserIsOperatorMiddleware.php'      => 'EnsureUserIsOperatorMiddleware.php.stub',
        ];

        foreach ($map as $filename => $stub) {
            $path = app_path("Http/Middleware/{$filename}");
            if (File::exists($path) && ! $force) {
                $this->skip("app/Http/Middleware/{$filename}");
                continue;
            }
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $this->stub($stub));
            $this->file("app/Http/Middleware/{$filename}");
        }
    }

    private function registerMiddlewareAliases(): void
    {
        $bootstrap = base_path('bootstrap/app.php');
        if (! File::exists($bootstrap)) {
            $this->warn('bootstrap/app.php not found. Register middleware aliases manually.');
            return;
        }

        $content = File::get($bootstrap);
        $marker = '->withMiddleware(function (Middleware $middleware): void {';

        if (! str_contains($content, $marker)) {
            $this->warn('Could not locate withMiddleware callback. Register middleware aliases manually.');
            return;
        }

        $aliases = [
            "'role.administrator' => \\App\\Http\\Middleware\\EnsureUserIsAdministratorMiddleware::class,",
            "'role.operator'      => \\App\\Http\\Middleware\\EnsureUserIsOperatorMiddleware::class,",
        ];

        $pending = [];
        foreach ($aliases as $alias) {
            $key = Str::between($alias, "'", "'");
            if (! preg_match("/['\"]".preg_quote($key, '/')."['\"]\\s*=>/", $content)) {
                $pending[] = "            {$alias}";
            }
        }

        if ($pending !== []) {
            $replacement = $marker."\n        \$middleware->alias([\n".implode("\n", $pending)."\n        ]);\n";
            File::put($bootstrap, str_replace($marker, $replacement, $content));
        }
    }

    // ── Views ─────────────────────────────────────────────────────────────────────

    /** @return list<string> */
    private function publishViews(bool $force): array
    {
        $stubBase = $this->stubPath('views');
        $viewBase = resource_path('views');
        $created = [];

        $this->copyStubDirectory($stubBase, $viewBase, $force, $created);

        return $created;
    }

    /** @param list<string> $created */
    private function copyStubDirectory(string $source, string $destination, bool $force, array &$created): void
    {
        File::ensureDirectoryExists($destination);

        foreach (File::allFiles($source) as $file) {
            $relative = $file->getRelativePathname();
            $target = $destination.DIRECTORY_SEPARATOR.$relative;

            if (File::exists($target) && ! $force) {
                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            File::copy($file->getPathname(), $target);
            $created[] = 'resources/views/'.$relative;
        }
    }

    private function publishAssets(bool $force): void
    {
        $cssStub = $this->stubPath('app.css.stub');
        $jsStub  = $this->stubPath('app.js.stub');
        $css     = resource_path('css/app.css');
        $js      = resource_path('js/app.js');

        // Write CSS when: file missing, --force, or file has not yet received our dashboard CSS
        $cssContent = File::exists($css) ? File::get($css) : '';
        if (! str_contains($cssContent, 'dashboard-shell') || $force) {
            File::ensureDirectoryExists(resource_path('css'));
            File::put($css, File::get($cssStub));
        }

        // Write JS when: file missing, --force, or our snippet not yet present
        $jsContent = File::exists($js) ? File::get($js) : '';
        if (! str_contains($jsContent, 'auth:install') || $force) {
            File::ensureDirectoryExists(resource_path('js'));
            $authSnippet = File::get($jsStub);
            // Keep existing bootstrap import, prepend our snippet
            $base = str_contains($jsContent, "import './bootstrap'") ? $jsContent : "import './bootstrap';\n";
            File::put($js, $authSnippet."\n".$base);
        }

        $staticCss = preg_replace('/^@(?:import|source)\\s+.*;\\s*$/m', '', File::get($cssStub));
        $staticCss = "/* Standalone Devtools auth UI; no Node.js or Vite build required. */\n".$staticCss;
        $staticCssPath = public_path('css/devtools-auth.css');
        $staticJsPath = public_path('js/devtools-auth.js');

        if (! File::exists($staticCssPath) || $force || ! str_contains(File::get($staticCssPath), 'auth-page')) {
            File::ensureDirectoryExists(dirname($staticCssPath));
            File::put($staticCssPath, $staticCss);
        }

        if (! File::exists($staticJsPath) || $force || ! str_contains(File::get($staticJsPath), '__DAVINGM__')) {
            File::ensureDirectoryExists(dirname($staticJsPath));
            File::copy($jsStub, $staticJsPath);
        }
    }

    // Publish static dashboard icons outside the Vite manifest.

    /** @return list<string> */
    private function publishIcons(bool $force): array
    {
        $source = $this->stubPath('icons');
        $destination = public_path('icons');
        $published = [];

        foreach (File::files($source) as $icon) {
            $target = $destination.DIRECTORY_SEPARATOR.$icon->getFilename();

            if (File::exists($target) && ! $force) {
                continue;
            }

            File::ensureDirectoryExists($destination);
            File::copy($icon->getPathname(), $target);
            $published[] = 'public/icons/'.$icon->getFilename();
        }

        return $published;
    }

    // Register the generated auth endpoints in the application route file.
    private function registerRoutes(): void
    {
        $routeFile = base_path('routes/web.php');
        if (! File::exists($routeFile)) {
            $this->warn('routes/web.php not found. Add auth routes manually.');
            return;
        }

        $existing = File::get($routeFile);
        $marker = "auth:install";

        if (str_contains($existing, $marker)) {
            $this->skip('routes/web.php (auth routes already registered)');
            return;
        }

        $routes = <<<'PHP'

// ── Auth routes — generated by artisan auth:install ───────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'create'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'create'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [\App\Http\Controllers\ProfileController::class, 'password'])->name('profile.password');

    // Administrator-only routes
    Route::middleware('role.administrator')->group(function () {
        Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    });
});
PHP;

        File::append($routeFile, $routes);
    }

    // ── Model: add role to fillable ───────────────────────────────────────────────

    private function updateUserModel(): void
    {
        $modelPath = app_path('Models/User.php');
        if (! File::exists($modelPath)) {
            return;
        }

        $content = File::get($modelPath);

        // Only add 'role' if not already present
        if (str_contains($content, "'role'")) {
            return;
        }

        $content = str_replace(
            "'name',\n        'email',\n        'password',",
            "'name',\n        'email',\n        'password',\n        'role',",
            $content
        );

        File::put($modelPath, $content);
    }

    // ── Stub helpers ─────────────────────────────────────────────────────────────

    private function stubPath(string $relative = ''): string
    {
        $base = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'auth';
        return $relative !== '' ? $base.DIRECTORY_SEPARATOR.$relative : $base;
    }

    private function stub(string $filename): string
    {
        return File::get($this->stubPath($filename));
    }
}

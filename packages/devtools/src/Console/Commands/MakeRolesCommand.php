<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeRolesCommand extends Command
{
    protected $signature = 'make:roles {roles* : Role names, for example admin editor}';
    protected $description = 'Generate role middleware and register role aliases';

    public function handle(): int
    {
        $bootstrap = base_path('bootstrap/app.php');
        if (!File::exists($bootstrap)) {
            $this->error('bootstrap/app.php was not found.');
            return self::FAILURE;
        }
        $aliases = [];
        foreach ($this->argument('roles') as $role) {
            $role = trim((string) $role);
            if ($role === '') continue;
            $class = 'EnsureUserIs'.Str::studly($role).'Middleware';
            $alias = 'role.'.Str::snake($role);
            $path = app_path("Http/Middleware/{$class}.php");
            if (!File::exists($path)) {
                File::ensureDirectoryExists(dirname($path));
                File::put($path, $this->middlewareSource($class, strtolower($role)));
                $this->info("Created app/Http/Middleware/{$class}.php");
            }
            $aliases[$alias] = '\\App\\Http\\Middleware\\'.$class.'::class';
        }
        if ($aliases === []) {
            $this->error('Provide at least one role name.');
            return self::FAILURE;
        }

        $content = File::get($bootstrap);
        $marker = '->withMiddleware(function (Middleware $middleware): void {';
        if (!str_contains($content, $marker)) {
            $this->error('Could not find Laravel’s withMiddleware callback in bootstrap/app.php; middleware files were created but aliases need manual registration.');
            return self::FAILURE;
        }
        $pending = [];
        foreach ($aliases as $alias => $class) {
            if (!preg_match("/['\"]".preg_quote($alias, '/')."['\"]\\s*=>/", $content)) {
                $pending[] = "            '{$alias}' => {$class},";
            }
        }
        if ($pending !== []) {
            $replacement = $marker."\n        \$middleware->alias([\n".implode("\n", $pending)."\n        ]);";
            File::put($bootstrap, str_replace($marker, $replacement, $content));
        }
        $this->info('Role middleware aliases registered in bootstrap/app.php.');
        return self::SUCCESS;
    }

    private function middlewareSource(string $class, string $role): string
    {
        return strtr(<<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class __CLASS__
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === '__ROLE__', 403, 'Unauthorized.');
        return $next($request);
    }
}
PHP, ['__CLASS__' => $class, '__ROLE__' => $role]);
    }
}

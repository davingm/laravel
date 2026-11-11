<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Commands;

use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Davingm\LaravelDevtools\Crud\Exceptions\ControllerGenerationException;
use Davingm\LaravelDevtools\Crud\Exceptions\ModelGenerationException;
use Davingm\LaravelDevtools\Crud\Exceptions\RouteManagerException;
use Davingm\LaravelDevtools\Crud\Exceptions\ViewGenerationException;
use Davingm\LaravelDevtools\Crud\Generators\ControllerGenerator;
use Davingm\LaravelDevtools\Crud\Generators\ControllerWriter;
use Davingm\LaravelDevtools\Crud\Generators\ModelGenerator;
use Davingm\LaravelDevtools\Crud\Generators\ModelWriter;
use Davingm\LaravelDevtools\Crud\Generators\RouteManager;
use Davingm\LaravelDevtools\Crud\Generators\RouteRegistrationResult;
use Davingm\LaravelDevtools\Crud\Generators\RouteStub;
use Davingm\LaravelDevtools\Crud\Generators\ViewGenerator;
use Davingm\LaravelDevtools\Crud\Generators\ViewWriter;
use Davingm\LaravelDevtools\Crud\Schema\SchemaInspector;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class CrudGenerateCommand extends Command
{
    protected $signature = 'crud:generate
        {model : Model or table name (e.g., Product or products)}
        {--dry-run : Preview without writing files}
        {--force : Overwrite existing files; auto-CRUD route blocks are always updated}';

    protected $description = 'Generate CRUD for a database table (model, controller, views, route)';
    protected $aliases = ['devtools:crud'];

    private array $totals = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
    private int $previewBytes = 0;

    public function __construct(
        private readonly ?SchemaInspector $inspector = null,
        private readonly ?ModelGenerator $modelGenerator = null,
        private readonly ?ControllerGenerator $controllerGenerator = null,
        private readonly ?ViewGenerator $viewGenerator = null,
        private readonly ?RouteManager $routeManager = null,
        private readonly ?RouteStub $routeStub = null,
        private readonly ?ModelWriter $modelWriter = null,
        private readonly ?ControllerWriter $controllerWriter = null,
        private readonly ?ViewWriter $viewWriter = null,
        private readonly ?array $paths = null,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->totals = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $this->previewBytes = 0;

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        if ($dryRun && $force) {
            $this->error('Cannot combine --force with --dry-run.');

            return self::FAILURE;
        }

        $input = trim((string) $this->argument('model'));
        $table = Str::snake(Str::plural($input));
        if ($input === '' || preg_match('/^[a-zA-Z0-9_]+$/D', $table) !== 1) {
            $this->error('Provide a valid model or table name, for example Product or products.');

            return self::FAILURE;
        }

        $modelName = Str::studly(Str::singular($table));
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $modelName) !== 1) {
            $this->error("Cannot derive a valid model class name from table [{$table}].");

            return self::FAILURE;
        }

        $this->info("Generating CRUD for table: {$table}");

        try {
            $definition = ($this->inspector ?? new SchemaInspector())->inspect($table);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->error('Schema inspection failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $paths = $this->resolvePaths();
        $modelWriter = $this->modelWriter ?? new ModelWriter($paths['models']);
        $controllerWriter = $this->controllerWriter ?? new ControllerWriter($paths['controllers']);
        $viewWriter = $this->viewWriter ?? new ViewWriter($paths['views']);
        $routeManager = $this->routeManager ?? new RouteManager($paths['routes']);
        $routeStub = $this->routeStub ?? new RouteStub();
        $modelGenerator = $this->modelGenerator ?? new ModelGenerator();
        $controllerGenerator = $this->controllerGenerator ?? new ControllerGenerator();
        $viewGenerator = $this->viewGenerator ?? new ViewGenerator();

        $steps = [
            [
                'label' => 'Model',
                'path' => 'app/Models/'.$modelName.'.php',
                'call' => fn (): array => $this->generateModel($definition, $modelName, $modelGenerator, $modelWriter, $paths['models'], $dryRun, $force),
            ],
            [
                'label' => 'Controller',
                'path' => 'app/Http/Controllers/'.$modelName.'Controller.php',
                'call' => fn (): array => $this->generateController($definition, $modelName, $controllerGenerator, $controllerWriter, $paths['controllers'], $dryRun, $force),
            ],
            [
                'label' => 'Views',
                'path' => 'resources/views/'.$table,
                'call' => fn (): array => $this->generateViews($definition, $table, $viewGenerator, $viewWriter, $paths['views'], $dryRun, $force),
            ],
            [
                'label' => 'Route',
                'path' => 'routes/web.php',
                'call' => fn (): array => $this->ensureRoute($definition, $modelName, $routeManager, $routeStub, $dryRun),
            ],
        ];

        foreach ($steps as $step) {
            try {
                $this->report($step['call']());
            } catch (ModelGenerationException|ControllerGenerationException|ViewGenerationException|RouteManagerException $exception) {
                $this->reportFailure($step['path'], $exception);
            } catch (RuntimeException|InvalidArgumentException $exception) {
                $this->reportFailure($step['path'], $exception);
            } catch (Throwable $exception) {
                $this->reportFailure($step['path'], $exception);
            }
        }

        if ($dryRun) {
            $this->line('Preview: '.$this->previewBytes.' generated bytes; no files were written.');
        }
        $this->printSummary($dryRun);

        return $this->totals['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return list<array{path: string, status: string, content?: string, reason?: string}> */
    private function generateModel(
        CrudDefinition $definition,
        string $modelName,
        ModelGenerator $generator,
        ModelWriter $writer,
        string $basePath,
        bool $dryRun,
        bool $force,
    ): array {
        $content = $generator->generate($definition);

        return [$this->writeFile(
            $basePath.DIRECTORY_SEPARATOR.$modelName.'.php',
            'app/Models/'.$modelName.'.php',
            $content,
            $dryRun,
            $force,
            fn (bool $overwrite): string => $writer->write($modelName, $content, $overwrite),
        )];
    }

    /** @return list<array{path: string, status: string, content?: string, reason?: string}> */
    private function generateController(
        CrudDefinition $definition,
        string $modelName,
        ControllerGenerator $generator,
        ControllerWriter $writer,
        string $basePath,
        bool $dryRun,
        bool $force,
    ): array {
        $className = $modelName.'Controller';
        $content = $generator->generate($definition);

        return [$this->writeFile(
            $basePath.DIRECTORY_SEPARATOR.$className.'.php',
            'app/Http/Controllers/'.$className.'.php',
            $content,
            $dryRun,
            $force,
            fn (bool $overwrite): string => $writer->write($className, $content, $overwrite),
        )];
    }

    /** @return list<array{path: string, status: string, content?: string, reason?: string}> */
    private function generateViews(
        CrudDefinition $definition,
        string $table,
        ViewGenerator $generator,
        ViewWriter $writer,
        string $basePath,
        bool $dryRun,
        bool $force,
    ): array {
        $views = $generator->generate($definition);
        $results = [];
        foreach ($views as $viewName => $content) {
            $results[] = $this->writeFile(
                $basePath.DIRECTORY_SEPARATOR.$table.DIRECTORY_SEPARATOR.$viewName.'.blade.php',
                'resources/views/'.$table.'/'.$viewName.'.blade.php',
                $content,
                $dryRun,
                $force,
                fn (bool $overwrite): string => $writer->write($table, $viewName, $content, $overwrite),
            );
        }

        return $results;
    }

    /** @return list<array{path: string, status: string, content?: string, reason?: string}> */
    private function ensureRoute(
        CrudDefinition $definition,
        string $modelName,
        RouteManager $manager,
        RouteStub $stub,
        bool $dryRun,
    ): array {
        if ($dryRun) {
            $content = $stub->render($definition->table, '\\App\\Http\\Controllers\\'.$modelName.'Controller');

            return [[
                'path' => $definition->table,
                'status' => 'would-add',
                'content' => $content,
            ]];
        }

        $result = $manager->ensureResourceRoute($definition);

        return [[
            'path' => $result->routeName,
            'status' => $result->status,
            'reason' => $result->skipReason ?? '',
        ]];
    }

    /** @return array{path: string, status: string, content?: string, reason?: string} */
    private function writeFile(
        string $absolutePath,
        string $displayPath,
        string $content,
        bool $dryRun,
        bool $force,
        callable $write,
    ): array {
        $exists = file_exists($absolutePath);
        if ($dryRun) {
            return [
                'path' => $displayPath,
                'status' => $exists ? 'would-skip' : 'would-create',
                'content' => $content,
                'reason' => $exists ? 'already exists' : '',
            ];
        }
        if ($exists && ! $force) {
            return ['path' => $displayPath, 'status' => 'skipped', 'reason' => 'already exists, use --force to overwrite'];
        }

        $write($force);

        return ['path' => $displayPath, 'status' => $exists ? 'updated' : 'created'];
    }

    /** @param list<array{path: string, status: string, content?: string, reason?: string}> $items */
    private function report(array $items): void
    {
        foreach ($items as $item) {
            if (isset($item['content'])) {
                $this->previewBytes += strlen($item['content']);
            }

            switch ($item['status']) {
                case 'created':
                    $this->totals['created']++;
                    $this->info('✓ Created '.$item['path']);
                    break;
                case 'updated':
                    $this->totals['updated']++;
                    $this->info('✓ Updated '.$item['path']);
                    break;
                case 'skipped':
                    $this->totals['skipped']++;
                    $this->warn('- Skipped '.$item['path'].' ('.$item['reason'].')');
                    break;
                case 'would-create':
                    $this->totals['created']++;
                    $this->info('Would create '.$item['path']);
                    break;
                case 'would-skip':
                    $this->totals['skipped']++;
                    $this->warn('Would skip '.$item['path'].' ('.$item['reason'].')');
                    break;
                case RouteRegistrationResult::STATUS_ADDED:
                    $this->info("✓ Added route block for '{$item['path']}'");
                    break;
                case RouteRegistrationResult::STATUS_UPDATED:
                    $this->info("~ Updated route block for '{$item['path']}'");
                    break;
                case RouteRegistrationResult::STATUS_SKIPPED:
                    $reason = $item['reason'] !== '' ? ': '.$item['reason'] : '';
                    $this->warn("- Skipped route for '{$item['path']}'{$reason}");
                    break;
                case 'would-add':
                    $this->info("Would add route block for '{$item['path']}'");
                    break;
            }
        }
    }

    private function reportFailure(string $path, Throwable $exception): void
    {
        $this->totals['failed']++;
        $this->error('✗ Failed '.$path.': '.$exception->getMessage());
    }

    private function printSummary(bool $dryRun): void
    {
        $this->line('Summary:');
        if ($dryRun) {
            $this->line('  '.$this->countLabel($this->totals['created'], 'file', 'files').' would be created');
            $this->line('  '.$this->countLabel($this->totals['skipped'], 'file', 'files').' would be skipped');
        } else {
            $this->line('  '.$this->countLabel($this->totals['created'], 'file', 'files').' created');
            $this->line('  '.$this->countLabel($this->totals['updated'], 'file', 'files').' updated');
            $this->line('  '.$this->countLabel($this->totals['skipped'], 'file', 'files').' skipped');
        }
        $this->line('  '.$this->countLabel($this->totals['failed'], 'error', 'errors'));
    }

    private function countLabel(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count === 1 ? $singular : $plural);
    }

    /** @return array{models: string, controllers: string, views: string, routes: string} */
    private function resolvePaths(): array
    {
        if ($this->paths !== null) {
            return $this->paths;
        }

        return [
            'models' => app_path('Models'),
            'controllers' => app_path('Http/Controllers'),
            'views' => resource_path('views'),
            'routes' => base_path('routes/web.php'),
        ];
    }
}

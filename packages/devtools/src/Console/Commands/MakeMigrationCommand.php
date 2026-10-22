<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Console\Commands;

use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Support\Str;

final class MakeMigrationCommand extends MigrateMakeCommand
{
    protected $signature = 'make:migration {name : Table name, for example product or users}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--path= : Migration location}
        {--realpath : Resolve the provided migration path as absolute}
        {--fullpath : Display the full migration path}';
    protected $description = 'Create a create-table migration from a short table name';

    public function __construct()
    {
        parent::__construct(app('migration.creator'), app('composer'));
    }

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        if ($this->option('create') || $this->option('table') || preg_match('/^(create|add|drop|update|modify)_/', Str::snake($name))) {
            parent::handle();
            return self::SUCCESS;
        }

        $table = Str::snake($name);
        if ($table === '') {
            $this->error('A table name is required.');
            return self::FAILURE;
        }
        if (!Str::endsWith($table, 's')) $table .= 's';
        $this->writeMigration("create_{$table}_table", $table, true);
        return self::SUCCESS;
    }
}

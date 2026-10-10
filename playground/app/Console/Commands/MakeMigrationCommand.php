<?php

namespace App\Console\Commands;

use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Support\Str;

class MakeMigrationCommand extends MigrateMakeCommand
{
    /**
     * Laravel discovers commands by constructing them to read their signatures.
     * Resolve the same services used by Laravel's built-in migration command
     * explicitly, since MigrationCreator has a required custom stub path.
     */
    public function __construct()
    {
        parent::__construct(app('migration.creator'), app('composer'));
    }

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:migration {name : Table name, e.g. flight or mahasiswa}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration (Deprecated)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a table migration using the framework table naming convention';

    /**
     * Create a table migration from a short table name.
     */
    public function handle(): int
    {
        $table = Str::snake(trim((string) $this->argument('name')));

        if ($table === '') {
            $this->error('A table name is required.');

            return self::FAILURE;
        }

        // This framework uses a simple trailing-s convention for every table,
        // including names that are not English words (e.g. mahasiswa -> mahasiswas).
        if (! Str::endsWith($table, 's')) {
            $table .= 's';
        }

        $migrationName = "create_{$table}_table";

        $this->writeMigration($migrationName, $table, true);

        return self::SUCCESS;
    }
}

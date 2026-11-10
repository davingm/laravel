<?php

declare(strict_types=1);

namespace Tests\Integration;

use Davingm\LaravelDevtools\Commands\CrudGenerateCommand;
use Illuminate\Support\Facades\Artisan;

final class CommandDiscoveryTest extends IntegrationTestCase
{
    public function test_command_registered_in_artisan(): void
    {
        self::assertArrayHasKey('crud:generate', Artisan::all());
    }

    public function test_command_alias_registered(): void
    {
        self::assertArrayHasKey('devtools:crud', Artisan::all());
    }

    public function test_command_has_correct_signature(): void
    {
        $command = Artisan::all()['crud:generate'];
        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('model'));
        self::assertTrue($definition->hasOption('dry-run'));
        self::assertTrue($definition->hasOption('force'));
    }

    public function test_old_autocrud_command_not_registered(): void
    {
        $command = Artisan::all()['crud:generate'];

        self::assertInstanceOf(CrudGenerateCommand::class, $command);
        self::assertNotSame('Davingm\\LaravelDevtools\\Console\\Commands\\AutoCrudCommand', $command::class);
    }
}

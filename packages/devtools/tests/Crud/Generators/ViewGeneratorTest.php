<?php

declare(strict_types=1);

namespace Tests\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Exceptions\ViewGenerationException;
use Davingm\LaravelDevtools\Crud\Generators\ViewGenerator;
use Davingm\LaravelDevtools\Crud\Schema\SchemaInspector;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;

final class ViewGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $container = $capsule->getContainer();
        $container->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstance('db.schema');
    }

    public function test_returns_three_views(): void
    {
        $this->createProductsTable();

        $views = (new ViewGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertSame(['index', 'create', 'edit'], array_keys($views));
    }

    public function test_index_contains_table_and_pagination(): void
    {
        $this->createProductsTable();
        $index = (new ViewGenerator())->generate((new SchemaInspector())->inspect('products'))['index'];

        self::assertStringContainsString('<table class="table table-bordered">', $index);
        self::assertStringContainsString('{{ $products->links() }}', $index);
        self::assertStringContainsString("route('products.create')", $index);
    }

    public function test_create_contains_form_and_csrf(): void
    {
        $this->createProductsTable();
        $create = (new ViewGenerator())->generate((new SchemaInspector())->inspect('products'))['create'];

        self::assertStringContainsString('<form method="POST"', $create);
        self::assertStringContainsString('@csrf', $create);
        self::assertStringContainsString("route('products.store')", $create);
    }

    public function test_edit_contains_put_method_spoofing(): void
    {
        $this->createProductsTable();
        $edit = (new ViewGenerator())->generate((new SchemaInspector())->inspect('products'))['edit'];

        self::assertStringContainsString("@method('PUT')", $edit);
        self::assertStringContainsString('route(\'products.update\', $product)', $edit);
    }

    public function test_edit_contains_error_alert_block(): void
    {
        $this->createProductsTable();
        $edit = (new ViewGenerator())->generate((new SchemaInspector())->inspect('products'))['edit'];

        self::assertStringContainsString('@if($errors->any())', $edit);
        self::assertStringContainsString('@foreach($errors->all() as $error)', $edit);
    }

    public function test_foreign_key_renders_select_with_controller_var(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->string('name');
        });

        $views = (new ViewGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertStringContainsString('@foreach($categories as $option)', $views['create']);
        self::assertStringContainsString('@foreach($categories as $option)', $views['edit']);
    }

    public function test_skips_timestamps_and_primary_key(): void
    {
        Schema::create('timestamped_products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $views = (new ViewGenerator())->generate((new SchemaInspector())->inspect('timestamped_products'));

        self::assertStringNotContainsString('name="id"', $views['create']);
        self::assertStringNotContainsString('name="created_at"', $views['create']);
        self::assertStringNotContainsString('name="updated_at"', $views['edit']);
    }

    public function test_rejects_composite_primary_key(): void
    {
        Schema::create('composite_products', function (Blueprint $table): void {
            $table->string('tenant');
            $table->string('code');
            $table->primary(['tenant', 'code']);
        });

        $this->expectException(ViewGenerationException::class);
        $this->expectExceptionMessage('composite primary key');
        (new ViewGenerator())->generate((new SchemaInspector())->inspect('composite_products'));
    }

    public function test_rejects_missing_primary_key(): void
    {
        Schema::create('keyless_products', function (Blueprint $table): void {
            $table->string('name');
        });

        $this->expectException(ViewGenerationException::class);
        $this->expectExceptionMessage('no primary key detected');
        (new ViewGenerator())->generate((new SchemaInspector())->inspect('keyless_products'));
    }

    private function createProductsTable(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->timestamps();
        });
    }
}

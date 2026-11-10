<?php

declare(strict_types=1);

namespace Tests\Crud\Schema;

use Davingm\LaravelDevtools\Crud\Data\ColumnDefinition;
use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Davingm\LaravelDevtools\Crud\Data\RelationshipDefinition;
use Davingm\LaravelDevtools\Crud\Schema\SchemaInspector;
use Davingm\LaravelDevtools\Crud\Schema\RelationshipInspector;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SchemaInspectorTest extends TestCase
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
    }

    public function test_it_inspects_columns_primary_key_defaults_nullable_and_foreign_keys(): void
    {
        Schema::create('inspector_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('inspector_products', function (Blueprint $table): void {
            $table->bigIncrements('product_key');
            $table->string('name')->default('draft');
            $table->unsignedInteger('quantity')->default(4);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('category_ref');
            $table->foreign('category_ref')->references('id')->on('inspector_categories');
        });

        $definition = (new SchemaInspector())->inspect('inspector_products', 'InspectorProduct');

        $this->assertSame('inspector_products', $definition->table);
        $this->assertSame('InspectorProduct', $definition->modelName);
        $this->assertSame(['product_key'], $definition->primaryKeys);
        $this->assertSame('product_key', $definition->primaryKey());
        $this->assertCount(6, $definition->columns);

        $columns = [];
        foreach ($definition->columns as $column) {
            $columns[$column->name] = $column;
        }

        $this->assertSame([
            'product_key',
            'name',
            'quantity',
            'description',
            'category_id',
            'category_ref',
        ], array_keys($columns));
        $this->assertInstanceOf(ColumnDefinition::class, $columns['product_key']);
        $this->assertTrue($columns['product_key']->primary);
        $this->assertTrue($columns['product_key']->autoIncrement);
        $rawColumns = [];
        foreach (Schema::getColumns('inspector_products') as $column) {
            $rawColumns[$column['name']] = $column['default'] ?? null;
        }
        $this->assertSame($rawColumns['name'], $columns['name']->default);
        $this->assertSame($rawColumns['quantity'], $columns['quantity']->default);
        $this->assertTrue($columns['description']->nullable);
        $this->assertFalse($columns['name']->nullable);

        $this->assertCount(1, $definition->relationships);
        $relationship = $definition->relationships[0];
        $this->assertInstanceOf(RelationshipDefinition::class, $relationship);
        $this->assertSame(['category_ref'], $relationship->columns);
        $this->assertSame('inspector_categories', $relationship->foreignTable);
        $this->assertSame(['id'], $relationship->foreignColumns);
        $this->assertSame('inspectorCategory', $relationship->method);
        $this->assertSame('InspectorCategory', $relationship->relatedModel);
        $this->assertFalse(in_array('category_id', $relationship->columns, true));
    }

    public function test_it_returns_no_relationships_for_a_table_without_foreign_keys(): void
    {
        Schema::create('inspector_standalone', function (Blueprint $table): void {
            $table->id();
            $table->string('category_id');
        });

        $definition = (new SchemaInspector())->inspect('inspector_standalone');

        $this->assertSame([], $definition->relationships);
    }

    public function test_it_reports_a_missing_table(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table [inspector_missing] does not exist');

        (new SchemaInspector())->inspect('inspector_missing');
    }

    public function test_it_keeps_composite_foreign_keys_explicitly_unsupported_for_eloquent_relations(): void
    {
        Schema::create('inspector_composite_parents', function (Blueprint $table): void {
            $table->string('tenant_code');
            $table->string('record_code');
            $table->primary(['tenant_code', 'record_code']);
        });

        Schema::create('inspector_composite_children', function (Blueprint $table): void {
            $table->id();
            $table->string('parent_tenant');
            $table->string('parent_record');
            $table->foreign(['parent_tenant', 'parent_record'])
                ->references(['tenant_code', 'record_code'])
                ->on('inspector_composite_parents');
        });

        $definition = (new SchemaInspector())->inspect('inspector_composite_children');
        $relationship = $definition->relationships[0];

        $this->assertSame(['parent_tenant', 'parent_record'], $relationship->columns);
        $this->assertSame(['tenant_code', 'record_code'], $relationship->foreignColumns);
        $this->assertTrue($relationship->isComposite());
        $this->assertFalse($relationship->supportsEloquentRelation());
    }

    public function test_it_names_multiple_relations_to_the_same_table_deterministically(): void
    {
        Schema::create('inspector_custom_categories', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('inspector_custom_products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('billing_category_id');
            $table->unsignedBigInteger('shipping_category_id');
            $table->foreign('billing_category_id')->references('id')->on('inspector_custom_categories');
            $table->foreign('shipping_category_id')->references('id')->on('inspector_custom_categories');
        });

        $relationships = (new SchemaInspector())->inspect('inspector_custom_products')->relationships;

        $this->assertSame(['billingCategory', 'shippingCategory'], array_map(
            static fn (RelationshipDefinition $relationship) => $relationship->method,
            $relationships,
        ));
        $this->assertSame(['InspectorCustomCategory', 'InspectorCustomCategory'], array_map(
            static fn (RelationshipDefinition $relationship) => $relationship->relatedModel,
            $relationships,
        ));
    }

    public function test_it_uses_the_singular_table_name_as_the_relationship_method_base(): void
    {
        $relationship = (new RelationshipInspector())->inspect([[
            'columns' => ['category_id'],
            'foreign_columns' => ['id'],
            'foreign_table' => 'categories',
        ]])[0];

        $this->assertSame('category', $relationship->method);
        $this->assertSame('Category', $relationship->relatedModel);
        $this->assertTrue($relationship->isNamed());
    }

    public function test_it_exposes_eloquent_primary_key_configuration(): void
    {
        Schema::create('inspector_integer_keys', function (Blueprint $table): void {
            $table->bigIncrements('record_key');
        });
        Schema::create('inspector_uuid_keys', function (Blueprint $table): void {
            $table->string('uuid')->primary();
        });
        Schema::create('inspector_composite_keys', function (Blueprint $table): void {
            $table->string('tenant');
            $table->unsignedInteger('record');
            $table->primary(['tenant', 'record']);
        });

        $auto = (new SchemaInspector())->inspect('inspector_integer_keys');
        $manual = new CrudDefinition('inspector_manual_integer_keys', ['record_key'], [
            new ColumnDefinition('record_key', 'integer', false, null, false, true),
        ], []);
        $uuid = (new SchemaInspector())->inspect('inspector_uuid_keys');
        $composite = (new SchemaInspector())->inspect('inspector_composite_keys');

        $this->assertSame('record_key', $auto->primaryKey());
        $this->assertTrue($auto->incrementing());
        $this->assertSame('int', $auto->keyType());
        $this->assertFalse($manual->incrementing());
        $this->assertSame('int', $manual->keyType());
        $this->assertFalse($uuid->incrementing());
        $this->assertSame('string', $uuid->keyType());
        $this->assertNull($composite->primaryKey());
        $this->assertTrue($composite->primaryKeyIsComposite());
        $this->assertFalse($composite->incrementing());
        $this->assertNull($composite->keyType());
    }

    public function test_it_normalizes_tinyint_one_to_boolean_without_changing_other_tinyints(): void
    {
        Schema::create('inspector_boolean_columns', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_active');
            $table->tinyInteger('retry_count');
        });

        $columns = [];
        foreach ((new SchemaInspector())->inspect('inspector_boolean_columns')->columns as $column) {
            $columns[$column->name] = $column;
        }

        $this->assertSame('boolean', $columns['is_active']->type);
        $this->assertSame('integer', $columns['retry_count']->type);
    }

    public function test_it_rejects_invalid_column_and_foreign_key_metadata(): void
    {
        $schema = $this->createMock(Builder::class);
        $schema->method('hasTable')->willReturn(true);
        $schema->method('getIndexes')->willReturn([]);
        $schema->method('getColumns')->willReturn([['name' => 'broken']]);
        $container = Facade::getFacadeApplication();
        $container->instance('db.schema', $schema);
        Facade::clearResolvedInstance('db.schema');

        try {
            (new SchemaInspector())->inspect('inspector_broken');
            $this->fail('Invalid column metadata should fail inspection.');
        } catch (\UnexpectedValueException $exception) {
            $this->assertStringContainsString('inspector_broken', $exception->getMessage());
        }

        $this->expectException(\UnexpectedValueException::class);
        (new RelationshipInspector())->inspect([['columns' => ['local_id'], 'foreign_columns' => [], 'foreign_table' => 'parents']]);
    }

    public function test_it_wraps_schema_read_failures_with_operation_and_table_context(): void
    {
        $cause = new \RuntimeException('driver failure');
        $schema = $this->createMock(Builder::class);
        $schema->method('hasTable')->willReturn(true);
        $schema->method('getIndexes')->willReturn([]);
        $schema->method('getColumns')->willThrowException($cause);
        $container = Facade::getFacadeApplication();
        $container->instance('db.schema', $schema);
        Facade::clearResolvedInstance('db.schema');

        try {
            (new SchemaInspector())->inspect('inspector_unreadable');
            $this->fail('Schema read failure should be reported.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('columns metadata', $exception->getMessage());
            $this->assertStringContainsString('inspector_unreadable', $exception->getMessage());
            $this->assertSame($cause, $exception->getPrevious());
        }
    }
}

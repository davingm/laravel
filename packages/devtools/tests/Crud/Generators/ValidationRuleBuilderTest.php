<?php

declare(strict_types=1);

namespace Tests\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Data\ColumnDefinition;
use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Davingm\LaravelDevtools\Crud\Data\RelationshipDefinition;
use Davingm\LaravelDevtools\Crud\Generators\ValidationRuleBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

final class ValidationRuleBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        Facade::setFacadeApplication($capsule->getContainer());
    }

    public function test_skips_primary_key_and_timestamps(): void
    {
        $definition = $this->definition([
            $this->column('id', 'bigint', primary: true, autoIncrement: true),
            $this->column('name', 'varchar', length: 255),
            $this->column('created_at', 'datetime'),
            $this->column('updated_at', 'datetime'),
            $this->column('deleted_at', 'datetime', nullable: true),
        ]);

        self::assertSame(['name' => ['required', 'string', 'max:255']], (new ValidationRuleBuilder())->build($definition));
    }

    public function test_varchar_with_length_produces_max_rule(): void
    {
        $rules = (new ValidationRuleBuilder())->build($this->definition([
            $this->column('name', 'varchar', length: 255),
        ], primaryKeys: []));

        self::assertSame(['required', 'string', 'max:255'], $rules['name']);
    }

    public function test_nullable_column_gets_nullable_not_required(): void
    {
        $rules = (new ValidationRuleBuilder())->build($this->definition([
            $this->column('description', 'text', nullable: true),
        ], primaryKeys: []));

        self::assertSame(['nullable', 'string'], $rules['description']);
    }

    public function test_nullable_with_default_skips_required(): void
    {
        $rules = (new ValidationRuleBuilder())->build($this->definition([
            $this->column('price', 'decimal', nullable: true, default: 0),
        ], primaryKeys: []));

        self::assertSame(['nullable', 'numeric'], $rules['price']);
    }

    public function test_boolean_column_uses_sometimes_not_required(): void
    {
        $rules = (new ValidationRuleBuilder())->build($this->definition([
            $this->column('is_active', 'boolean'),
        ], primaryKeys: []));

        self::assertSame(['sometimes', 'boolean'], $rules['is_active']);
    }

    public function test_foreign_key_gets_exists_rule(): void
    {
        $relationship = new RelationshipDefinition('products_category_id_foreign', ['category_id'], 'categories', ['id']);
        $relationship->assignNames('category', 'Category');
        $rules = (new ValidationRuleBuilder())->build($this->definition([
            $this->column('category_id', 'bigint'),
        ], primaryKeys: [], relationships: [$relationship]));

        self::assertSame(['required', 'integer', 'exists:categories,id'], $rules['category_id']);
    }

    public function test_composite_foreign_key_skips_exists_and_warns(): void
    {
        $relationship = new RelationshipDefinition(null, ['category_id', 'locale'], 'categories', ['id', 'locale']);
        $builder = new ValidationRuleBuilder();
        $rules = $builder->build($this->definition([
            $this->column('category_id', 'bigint'),
            $this->column('locale', 'varchar', length: 5),
        ], primaryKeys: [], relationships: [$relationship]));

        self::assertSame(['required', 'integer'], $rules['category_id']);
        self::assertSame('composite foreign key: exists rule skipped', $builder->getWarnings()['category_id']);
        self::assertSame([], array_values(array_filter(
            $rules['category_id'],
            static fn (string $rule): bool => str_starts_with($rule, 'exists:'),
        )));
    }

    public function test_datetime_column_gets_date_rule(): void
    {
        $rules = (new ValidationRuleBuilder())->build($this->definition([
            $this->column('published_at', 'datetime', nullable: true),
        ], primaryKeys: []));

        self::assertSame(['nullable', 'date'], $rules['published_at']);
    }

    public function test_unknown_type_gets_only_required_or_nullable(): void
    {
        $rules = (new ValidationRuleBuilder())->build($this->definition([
            $this->column('custom_required', 'custom_thing'),
            $this->column('custom_optional', 'custom_thing', nullable: true),
        ], primaryKeys: []));

        self::assertSame(['required'], $rules['custom_required']);
        self::assertSame(['nullable'], $rules['custom_optional']);
    }

    /** @param list<ColumnDefinition> $columns @param list<string> $primaryKeys @param list<RelationshipDefinition> $relationships */
    private function definition(array $columns, array $primaryKeys = ['id'], array $relationships = []): CrudDefinition
    {
        return new CrudDefinition('records', $primaryKeys, $columns, $relationships);
    }

    private function column(
        string $name,
        string $type,
        bool $nullable = false,
        mixed $default = null,
        bool $autoIncrement = false,
        bool $primary = false,
        ?int $length = null,
    ): ColumnDefinition {
        return new ColumnDefinition($name, $type, $nullable, $default, $autoIncrement, $primary, $length);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Davingm\LaravelDevtools\Crud\Data\ColumnDefinition;
use Davingm\LaravelDevtools\Crud\Exceptions\ModelGenerationException;
use Davingm\LaravelDevtools\Crud\Generators\ModelGenerator;
use Davingm\LaravelDevtools\Crud\Schema\SchemaInspector;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;

final class ModelGeneratorTest extends TestCase
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

    public function test_generates_basic_model_for_simple_table(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->text('description')->nullable();
        });

        $code = (new ModelGenerator())->generate((new SchemaInspector())->inspect('products'));

        $this->assertStringContainsString('class Product extends Model', $code);
        $this->assertStringContainsString("protected \$fillable = ['name', 'price', 'description'];", $code);
        $this->assertStringNotContainsString('$primaryKey', $code);
        $this->assertStringNotContainsString('$incrementing', $code);
        $this->assertStringNotContainsString('$keyType', $code);
    }

    public function test_generates_relations(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
        });

        $code = (new ModelGenerator())->generate((new SchemaInspector())->inspect('products'));

        $this->assertStringContainsString('public function category(): BelongsTo', $code);
        $this->assertStringContainsString('return $this->belongsTo(\\App\\Models\\Category::class);', $code);
        $this->assertStringContainsString('use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;', $code);
    }

    public function test_skips_default_primary_key_config(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        $code = (new ModelGenerator())->generate((new SchemaInspector())->inspect('products'));

        $this->assertStringNotContainsString('protected $primaryKey', $code);
        $this->assertStringNotContainsString('public $incrementing', $code);
        $this->assertStringNotContainsString('protected $keyType', $code);
    }

    public function test_emits_custom_primary_key(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->string('product_code')->primary();
            $table->string('name');
        });

        $code = (new ModelGenerator())->generate((new SchemaInspector())->inspect('products'));

        $this->assertStringContainsString("protected \$primaryKey = 'product_code';", $code);
        $this->assertStringContainsString('public $incrementing = false;', $code);
        $this->assertStringContainsString("protected \$keyType = 'string';", $code);
    }

    public function test_rejects_composite_primary_key(): void
    {
        Schema::create('product_keys', function (Blueprint $table): void {
            $table->string('tenant');
            $table->string('product');
            $table->primary(['tenant', 'product']);
        });

        try {
            (new ModelGenerator())->generate((new SchemaInspector())->inspect('product_keys'));
            $this->fail('A composite primary key must be rejected.');
        } catch (ModelGenerationException $exception) {
            $this->assertStringContainsString('composite primary key', $exception->getMessage());
        }
    }

    public function test_rejects_missing_primary_key(): void
    {
        Schema::create('audit_entries', function (Blueprint $table): void {
            $table->string('message');
        });

        try {
            (new ModelGenerator())->generate((new SchemaInspector())->inspect('audit_entries'));
            $this->fail('A missing primary key must be rejected.');
        } catch (ModelGenerationException $exception) {
            $this->assertStringContainsString('no primary key detected', $exception->getMessage());
        }
    }

    public function test_emits_casts_for_boolean_and_datetime(): void
    {
        $definition = new CrudDefinition('products', ['id'], [
            new ColumnDefinition('id', 'bigint', false, null, true, true),
            new ColumnDefinition('is_active', 'boolean', false, null, false, false),
            new ColumnDefinition('published_at', 'datetime', true, null, false, false),
        ], []);
        $code = (new ModelGenerator())->generate($definition);

        $this->assertStringContainsString("'is_active' => 'bool'", $code);
        $this->assertStringContainsString("'published_at' => 'datetime'", $code);
    }

    public function test_marks_composite_relationships_as_skipped(): void
    {
        Schema::create('composite_parents', function (Blueprint $table): void {
            $table->string('tenant');
            $table->string('code');
            $table->primary(['tenant', 'code']);
        });
        Schema::create('composite_children', function (Blueprint $table): void {
            $table->id();
            $table->string('parent_tenant');
            $table->string('parent_code');
            $table->foreign(['parent_tenant', 'parent_code'])
                ->references(['tenant', 'code'])
                ->on('composite_parents');
        });

        $code = (new ModelGenerator())->generate((new SchemaInspector())->inspect('composite_children'));

        $this->assertStringContainsString('// TODO: composite relationship skipped — not supported by this generator yet', $code);
        $this->assertStringNotContainsString('function compositeParent', $code);
    }

    public function test_output_is_valid_php(): void
    {
        if (! function_exists('proc_open')) {
            $this->markTestSkipped('proc_open is disabled; PHP lint subprocess is unavailable.');
        }

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        $code = (new ModelGenerator())->generate((new SchemaInspector())->inspect('products'));
        $path = tempnam(sys_get_temp_dir(), 'generated-model-');
        self::assertNotFalse($path);

        try {
            self::assertSame(strlen($code), file_put_contents($path, $code));
            $pipes = [];
            $process = proc_open([PHP_BINARY, '-l', $path], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            $this->assertSame(0, $exitCode, $output);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}

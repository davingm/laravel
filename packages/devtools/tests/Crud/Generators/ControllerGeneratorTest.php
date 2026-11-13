<?php

declare(strict_types=1);

namespace Tests\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Exceptions\ControllerGenerationException;
use Davingm\LaravelDevtools\Crud\Data\ColumnDefinition;
use Davingm\LaravelDevtools\Crud\Data\CrudDefinition;
use Davingm\LaravelDevtools\Crud\Generators\ControllerGenerator;
use Davingm\LaravelDevtools\Crud\Generators\ControllerStub;
use Davingm\LaravelDevtools\Crud\Schema\SchemaInspector;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;

final class ControllerGeneratorTest extends TestCase
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

    public function test_generates_basic_controller(): void
    {
        $this->createProductsTable();
        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertStringContainsString('class ProductController extends Controller', $code);
        self::assertStringContainsString('public function index(): View', $code);
        self::assertStringContainsString('public function store(Request $request): RedirectResponse', $code);
        self::assertStringContainsString('public function edit(\\App\\Models\\Product $product): View', $code);
        self::assertStringContainsString('public function update(Request $request, \\App\\Models\\Product $product): RedirectResponse', $code);
        self::assertStringContainsString('public function destroy(\\App\\Models\\Product $product): RedirectResponse', $code);
    }

    public function test_uses_fqcn_for_model(): void
    {
        $this->createProductsTable();
        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertStringContainsString('\\App\\Models\\Product::create', $code);
        self::assertStringContainsString('\\App\\Models\\Product $product', $code);
    }

    public function test_does_not_emit_show_method(): void
    {
        $this->createProductsTable();
        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertStringNotContainsString('public function show(', $code);
    }

    public function test_uses_configured_pagination(): void
    {
        $this->createProductsTable();
        $code = (new ControllerGenerator(perPage: 25))->generate((new SchemaInspector())->inspect('products'));

        self::assertStringContainsString('paginate(25)', $code);
    }

    public function test_includes_validation_for_columns(): void
    {
        $definition = new CrudDefinition('products', ['id'], [
            new ColumnDefinition('id', 'bigint', false, null, true, true),
            new ColumnDefinition('name', 'varchar', false, null, false, false, 255),
            new ColumnDefinition('price', 'decimal', false, null, false, false),
        ], []);
        $code = (new ControllerGenerator())->generate($definition);

        self::assertStringContainsString("'name' => ['required', 'string', 'max:255']", $code);
    }

    public function test_excludes_timestamps_from_validation(): void
    {
        Schema::create('timestamped_products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('timestamped_products'));
        $validation = strstr($code, '$request->validate(');
        self::assertNotFalse($validation);
        $validation = substr($validation, 0, strpos($validation, ');'));

        self::assertStringNotContainsString('created_at', $validation);
        self::assertStringNotContainsString('updated_at', $validation);
    }

    public function test_rejects_composite_primary_key(): void
    {
        Schema::create('composite_products', function (Blueprint $table): void {
            $table->string('tenant');
            $table->string('code');
            $table->primary(['tenant', 'code']);
        });

        $this->expectException(ControllerGenerationException::class);
        $this->expectExceptionMessage('composite primary key');
        (new ControllerGenerator())->generate((new SchemaInspector())->inspect('composite_products'));
    }

    public function test_rejects_missing_primary_key(): void
    {
        Schema::create('keyless_products', function (Blueprint $table): void {
            $table->string('name');
        });

        $this->expectException(ControllerGenerationException::class);
        $this->expectExceptionMessage('no primary key detected');
        (new ControllerGenerator())->generate((new SchemaInspector())->inspect('keyless_products'));
    }

    public function test_warnings_rendered_for_composite_fk(): void
    {
        Schema::create('composite_categories', function (Blueprint $table): void {
            $table->string('tenant');
            $table->string('code');
            $table->primary(['tenant', 'code']);
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('category_tenant');
            $table->string('category_code');
            $table->foreign(['category_tenant', 'category_code'])
                ->references(['tenant', 'code'])
                ->on('composite_categories');
        });

        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertStringContainsString('// WARNING: category_code: composite foreign key: exists rule skipped', $code);
        self::assertStringContainsString('// WARNING: category_tenant: composite foreign key: exists rule skipped', $code);
    }

    public function test_create_passes_dropdown_data(): void
    {
        $this->createCategoryTable();
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
        });

        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertStringContainsString('$categories = \\App\\Models\\Category::query()->orderBy(\'id\')->get();', $code);
        self::assertStringContainsString("compact('categories')", $code);
    }

    public function test_edit_passes_dropdown_data_and_model(): void
    {
        $this->createCategoryTable();
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
        });

        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertStringContainsString("compact('product', 'categories')", $code);
    }

    public function test_multiple_foreign_keys_emit_multiple_queries(): void
    {
        $this->createCategoryTable();
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('brand_id')->constrained('brands');
        });

        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertSame(2, substr_count($code, '$categories = \\App\\Models\\Category::query()->orderBy(\'id\')->get();'));
        self::assertSame(2, substr_count($code, '$brands = \\App\\Models\\Brand::query()->orderBy(\'id\')->get();'));
        self::assertStringContainsString("compact('brands', 'categories')", $code);
    }

    public function test_no_foreign_keys_means_no_extra_queries(): void
    {
        $this->createProductsTable();

        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertStringContainsString("return view('pages.products.create');", $code);
        self::assertStringNotContainsString('orderBy(', $code);
    }

    public function test_composite_fk_does_not_emit_dropdown(): void
    {
        Schema::create('composite_categories', function (Blueprint $table): void {
            $table->string('tenant');
            $table->string('code');
            $table->primary(['tenant', 'code']);
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('category_tenant');
            $table->string('category_code');
            $table->foreign(['category_tenant', 'category_code'])
                ->references(['tenant', 'code'])
                ->on('composite_categories');
        });

        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('products'));

        self::assertStringNotContainsString('CompositeCategory::query()', $code);
        self::assertStringNotContainsString('orderBy(', $code);
    }

    public function test_controller_stub_output_is_valid_php(): void
    {
        if (! function_exists('proc_open')) {
            $this->markTestSkipped('proc_open is disabled; PHP lint subprocess is unavailable.');
        }

        $code = (new ControllerStub())->render(
            namespace: 'App\\Http\\Controllers',
            className: 'ProductController',
            modelFqcn: '\\App\\Models\\Product',
            modelVar: 'product',
            table: 'products',
            validationRules: ['name' => ['required', 'string', 'max:255']],
            perPage: '15',
        );
        $path = tempnam(sys_get_temp_dir(), 'generated-controller-stub-');
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

            self::assertSame(0, $exitCode, $output);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_generated_controller_output_is_valid_php(): void
    {
        if (! function_exists('proc_open')) {
            $this->markTestSkipped('proc_open is disabled; PHP lint subprocess is unavailable.');
        }
        $this->createProductsTable();
        $code = (new ControllerGenerator())->generate((new SchemaInspector())->inspect('products'));
        $path = tempnam(sys_get_temp_dir(), 'generated-controller-');
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

            self::assertSame(0, $exitCode, $output);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function createProductsTable(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->decimal('price', 8, 2);
        });
    }

    private function createCategoryTable(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
    }
}

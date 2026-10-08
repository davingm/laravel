<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CrudGenerateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_crud_from_database_metadata_and_skips_existing_files_on_rerun(): void
    {
        Schema::create('crud_generator_categories', function ($table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('crud_generator_products', function ($table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->dateTime('published_at')->nullable();
            $table->foreignId('crud_generator_category_id')->nullable()->constrained('crud_generator_categories');
            $table->unsignedBigInteger('unconstrained_category_id')->nullable();
            $table->timestamps();
        });

        $routePath = base_path('app/routes/web.php');
        $originalRoutes = File::get($routePath);
        $generatedFiles = [
            app_path('Models/CrudGeneratorProduct.php'),
            app_path('Http/Controllers/CrudGeneratorProductController.php'),
            base_path('src/pages/crud-generator-products/index.blade.php'),
            base_path('src/pages/crud-generator-products/create.blade.php'),
            base_path('src/pages/crud-generator-products/edit.blade.php'),
        ];

        try {
            $this->artisan('crud:generate', ['name' => 'CrudGeneratorProduct'])
                ->expectsOutputToContain('CRUD generation completed successfully.')
                ->assertExitCode(0);

            $model = File::get($generatedFiles[0]);
            $controller = File::get($generatedFiles[1]);
            $createView = File::get($generatedFiles[3]);
            $editView = File::get($generatedFiles[4]);
            $routes = File::get($routePath);

            $this->assertStringContainsString("'name'", $model);
            $this->assertStringContainsString("'description'", $model);
            $fillableStart = strpos($model, 'protected $fillable');
            $fillableEnd = strpos($model, '];', $fillableStart);
            $fillable = substr($model, $fillableStart, $fillableEnd - $fillableStart);
            $this->assertStringNotContainsString("'id'", $fillable);
            $this->assertStringContainsString('function crudGeneratorCategory(): BelongsTo', $model);
            $this->assertStringNotContainsString('unconstrainedCategory', $model);
            $this->assertStringContainsString("'exists:crud_generator_categories,id'", $controller);
            $this->assertStringContainsString("DB::table('crud_generator_categories')", $controller);
            $this->assertStringContainsString("'published_at' => ['nullable', 'date_format:Y-m-d\\\\TH:i']", $controller);
            $this->assertStringContainsString('function index(): View', $controller);
            $this->assertStringContainsString('function store(Request $request): RedirectResponse', $controller);
            $this->assertStringContainsString('function update(Request $request', $controller);
            $this->assertStringContainsString('function destroy(', $controller);
            $this->assertStringContainsString('<textarea name="description"', $createView);
            $this->assertStringContainsString('<select name="crud_generator_category_id"', $createView);
            $this->assertStringNotContainsString('<select name="unconstrained_category_id"', $createView);
            $this->assertStringNotContainsString('$model->', $createView);
            $this->assertStringContainsString("old('name', \$crudGeneratorProduct->name)", $editView);
            $this->assertStringContainsString("old('crud_generator_category_id', \$crudGeneratorProduct->crud_generator_category_id)", $editView);
            $this->assertStringContainsString("Route::resource('crud-generator-products'", $routes);
            $this->assertStringContainsString("'crud-generator-products'", $routes);
            $this->assertStringContainsString("'crud-generator-products/*'", $routes);

            $existingContents = [];
            foreach ([0, 1, 2, 3, 4] as $index) {
                $existingContents[$index] = "user-owned-file-{$index}";
                File::put($generatedFiles[$index], $existingContents[$index]);
            }
            $this->artisan('crud:generate', ['name' => 'CrudGeneratorProduct'])
                ->expectsOutputToContain('already exists. Skipping...')
                ->assertExitCode(0);

            foreach ($existingContents as $index => $contents) {
                $this->assertSame($contents, File::get($generatedFiles[$index]));
            }
            $this->assertSame(1, substr_count(File::get($routePath), "Route::resource('crud-generator-products'"));
        } finally {
            File::put($routePath, $originalRoutes);
            foreach ($generatedFiles as $path) {
                File::delete($path);
            }
        }
    }

    public function test_it_reports_a_missing_table_without_creating_files(): void
    {
        $this->artisan('crud:generate', ['name' => 'CrudGeneratorMissing'])
            ->expectsOutputToContain('Table "crud_generator_missings" does not exist.')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist(app_path('Models/CrudGeneratorMissing.php'));
    }
}
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\Artisan;

final class HttpCrudFlowTest extends IntegrationTestCase
{
    public function test_index_page_loads(): void
    {
        $this->generateAndLoadRoutes();

        $this->get('/products')
            ->assertOk()
            ->assertSee('<table', false)
            ->assertSee('Product List');
    }

    public function test_create_page_loads(): void
    {
        $this->generateAndLoadRoutes();

        $this->get('/products/create')
            ->assertOk()
            ->assertSee('<form', false)
            ->assertSee('name="name"', false);
    }

    public function test_store_creates_record(): void
    {
        $this->generateAndLoadRoutes();
        $category = $this->createCategory();

        $this->post('/products', $this->validProductData($category->getKey()))
            ->assertRedirect(route('products.index'));

        self::assertDatabaseHas('products', [
            'name' => 'Desk lamp',
            'category_id' => $category->getKey(),
        ], 'testing');
    }

    public function test_store_validation_error_does_not_create_record(): void
    {
        $this->generateAndLoadRoutes();

        $this->from('/products/create')
            ->post('/products', [])
            ->assertRedirect('/products/create')
            ->assertSessionHasErrors('name');

        self::assertDatabaseCount('products', 0, 'testing');
    }

    public function test_edit_page_loads_with_existing_values(): void
    {
        $this->generateAndLoadRoutes();
        $product = $this->createProduct();

        $this->get('/products/'.$product->getKey().'/edit')
            ->assertOk()
            ->assertSee('<form', false)
            ->assertSee('value="Desk lamp"', false);
    }

    public function test_update_modifies_record(): void
    {
        $this->generateAndLoadRoutes();
        $product = $this->createProduct();
        $category = $this->createCategory('Office');

        $this->put('/products/'.$product->getKey(), $this->validProductData($category->getKey(), 'Reading lamp'))
            ->assertRedirect(route('products.index'));

        self::assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'name' => 'Reading lamp',
            'category_id' => $category->getKey(),
        ], 'testing');
    }

    public function test_destroy_deletes_record(): void
    {
        $this->generateAndLoadRoutes();
        $product = $this->createProduct();

        $this->delete('/products/'.$product->getKey())
            ->assertRedirect(route('products.index'));

        self::assertDatabaseCount('products', 0, 'testing');
    }

    public function test_foreign_key_dropdown_contains_related_name(): void
    {
        $this->generateAndLoadRoutes();
        $this->createCategory('Electronics');

        $this->get('/products/create')
            ->assertOk()
            ->assertSee('name="category_id"', false)
            ->assertSee('<select', false)
            ->assertSee('Electronics');
    }

    public function test_boolean_field_is_rendered_as_checkbox(): void
    {
        $this->generateAndLoadRoutes();

        $model = file_get_contents($this->appBasePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR.'Product.php');
        $controller = file_get_contents($this->appBasePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'ProductController.php');
        self::assertIsString($model);
        self::assertIsString($controller);
        self::assertStringContainsString("'is_active' => 'bool'", $model);
        self::assertStringContainsString("'is_active' => ['sometimes', 'boolean']", $controller);

        $this->get('/products/create')
            ->assertOk()
            ->assertSee('name="is_active"', false)
            ->assertSee('type="checkbox"', false);
    }

    private function generateAndLoadRoutes(): void
    {
        self::assertSame(0, Artisan::call('crud:generate', ['model' => 'Product']));

        $routesFile = $this->appBasePath.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php';
        $this->app['router']->middleware('web')->group(function () use ($routesFile): void {
            require $routesFile;
        });
    }

    private function createCategory(string $name = 'Lighting'): \App\Models\Category
    {
        return \App\Models\Category::query()->create(['name' => $name]);
    }

    private function createProduct(): \App\Models\Product
    {
        return \App\Models\Product::query()->create($this->validProductData($this->createCategory()->getKey()));
    }

    /** @return array{name: string, description: string, price: float, category_id: int, is_active: int} */
    private function validProductData(int $categoryId, string $name = 'Desk lamp'): array
    {
        return [
            'name' => $name,
            'description' => 'Adjustable reading light',
            'price' => 24.50,
            'category_id' => $categoryId,
            'is_active' => 1,
        ];
    }
}

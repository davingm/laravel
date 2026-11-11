# Getting Started

This guide walks through generating a CRUD for a `products` table in a fresh Laravel 11 application.

## 1. Install the package

From the Laravel application root, run:

```bash
composer require --dev davingm/laravel-devtools
```

The package is a Composer plugin. If Composer asks whether to allow it, approve the plugin for this project. Laravel package discovery registers the service provider and its Artisan commands.

## 2. Create the tables

Create a migration for products and categories. The foreign key lets the generator identify the category relationship.

```bash
./artisan make:migration create_categories_table
./artisan make:migration create_products_table
```

Use a migration like this for the products table:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
```

Run migrations for your configured development database:

```bash
./artisan migrate
```

## 3. Generate the CRUD

Run the command from the Laravel application root:

```bash
./artisan crud:generate Product
```

The command inspects the active database connection. It does not parse migration files. Use `--dry-run` to preview the proposed changes before writing files.

## 4. Review the generated files

For this example, the generator creates:

- `app/Models/Product.php`
- `app/Http/Controllers/ProductController.php`
- `resources/views/products/index.blade.php`
- `resources/views/products/create.blade.php`
- `resources/views/products/edit.blade.php`
- A marked resource route block in `routes/web.php`

Generated routes exclude the `show` action. The index action paginates products, and the generated forms include the detected category relationship.

## 5. Open the page

Start the Laravel development server and visit the index route:

```bash
./artisan serve
```

Open [http://127.0.0.1:8000/products](http://127.0.0.1:8000/products). Confirm the page loads, then try creating, editing, and deleting a product.

## Troubleshooting

### The route returns 404

Confirm the command added a route block to `routes/web.php`, then inspect the registered routes:

```bash
./artisan route:list --path=products
```

Check that the application is using the expected route file and that the generated controller class exists.

### Laravel reports that a view cannot be found

Confirm the files are under `resources/views/products` and that the route uses the generated controller. The generator derives this view directory from the database table name.

### A column is missing from the form or index

The generator excludes the primary key and timestamp columns from ordinary form fields; the index also excludes timestamps. Check the active database schema and foreign key metadata. Column types and metadata can vary by database driver. See [Limitations](limitations.md) for cases where metadata is unavailable or unsupported.


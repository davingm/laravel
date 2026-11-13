# Laravel Devtools

Laravel development commands, an auth starter, CRUD generation, and an optional borrowing-system preset for Laravel applications.

[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel 11](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![License: AGPL-3.0-only](https://img.shields.io/badge/License-AGPL--3.0--only-blue)](https://www.gnu.org/licenses/agpl-3.0.html)

## Requirements

- PHP 8.2 or newer.
- Laravel 11, 12, and 13 are allowed by the package manifest. The auth starter and borrowing preset have been exercised in an isolated Laravel 12 application; Laravel 11 and 13 are not part of that verification.

## Installation

Install the package as a development dependency from your Laravel application root:

```bash
composer require --dev davingm/laravel-devtools
```

The package is a Composer plugin. Allow the plugin when Composer prompts you. Laravel package discovery registers the Artisan commands.

## Quick Start

1. Create and migrate a `categories` table and a `products` table with these columns:

   ```text
   categories: id, name
   products: id, category_id (foreign key), name, price, description,
             is_active, created_at, updated_at
   ```

   The `category_id` foreign key should reference `categories.id`. Ensure the application has an `App\Models\Category` model for the generated relationship dropdown.

2. Run the migrations against the database configured for the application:

   ```bash
   php artisan migrate
   ```

3. Generate the CRUD from the application root:

   ```bash
   php artisan crud:generate Product
   ```

4. Review the generated model, controller, three Blade views, and marked route block listed below.

5. Start the application with `php artisan serve` and open [http://127.0.0.1:8000/products](http://127.0.0.1:8000/products).

For a detailed walkthrough, see [Getting Started](docs/getting-started.md).

## Auth Starter and Presets

Install the auth starter on a clean Laravel application. Its CSS and JavaScript are copied into `public/`, so the auth and dashboard UI work without npm or Vite:

```bash
php artisan auth:install
```

After the auth starter is installed, add the borrowing system based on the repository's `references/preset-peminjaman` UI and workflows:

```bash
php artisan devtools:install --preset=peminjaman
```

The preset keeps one dashboard at `/dashboard`, adds role based links to the existing sidebar, and places its pages directly under `resources/views/pages/` (`tools`, `categories`, `loans`, `returns`, and `users`). `/peminjaman/dashboard` redirects to the same dashboard. Administrators retain full record and user management. Operators can add tools and categories, see all loan records (including approved loans shown as `Dipinjam`), and handle approvals and returns, but cannot manage users. Regular users can select multiple tools and quantities in one request. The dashboard header and sidebar are black, the workspace is near-black `#080808`, and dashboard tables, forms, and panels use `#0A0A0A`. CSS and JavaScript are served locally without npm or Vite.

## What Gets Generated

For the `products` table, the command generates:

- `app/Models/Product.php` — Eloquent model with fillable columns, supported casts, and detected single-column relationships.
- `app/Http/Controllers/ProductController.php` — paginated index, create, store, edit, update, and destroy actions with basic validation.
- `resources/views/pages/products/index.blade.php` — paginated table and CRUD actions.
- `resources/views/pages/products/create.blade.php` — creation form.
- `resources/views/pages/products/edit.blade.php` — edit form.
- A marked resource route block in `routes/web.php`; the generated resource excludes the `show` action.

A detected single-column foreign key such as `category_id` creates a relationship dropdown. Boolean fields use checkboxes. The generated route block can be updated on subsequent runs without adding a duplicate block.

## Commands Reference

```text
crud:generate {model} {--dry-run} {--force}
```

The required `model` argument accepts a model name or table name, for example `Product` or `products`.

- `--dry-run` previews generated files and route changes without writing files.
- `--force` overwrites existing model, controller, and view files. Auto CRUD route blocks are updated whenever the command runs.
- The options cannot be combined.
- Existing model, controller, and view files are skipped unless `--force` is provided. A recognized manual route may cause route registration to be skipped.

The legacy command name `devtools:crud` remains available as a silent alias. New projects should use `crud:generate`; see [Upgrading](UPGRADING.md) for the alias status and migration notes.

For full option details and output behavior, see [Commands](docs/commands.md).

## Example

Suppose the active database has these tables:

```text
categories
- id
- name

products
- id
- category_id (foreign key to categories.id)
- name
- price
- description (nullable)
- is_active
- created_at
- updated_at
```

After the migrations have run, execute:

```bash
php artisan crud:generate Product
```

## auth:install

Scaffolds a complete auth starter on a clean Laravel 12 project:

```bash
php artisan auth:install
```

What it installs:
- Login and register views using the dashboard UI kit
- Dashboard, profile, and user management pages
- `EnsureUserIsAdministrator` and `EnsureUserIsOperator` middleware with `role.administrator` and `role.operator` aliases
- Migration that adds a `role` column to the `users` table
- `AuthSeeder` with 3 default users — credentials below, **change before deploying**

| Email | Password | Role |
|---|---|---|
| admin@example.com | password | administrator |
| operator@example.com | password | operator |
| user@example.com | password | user |

The command refuses to run if auth controllers, auth views, or the role migration already exist. Pass `--force` to overwrite existing files.

The auth UI publishes standalone CSS and JavaScript under `public/`; login and dashboard styling do not require Node.js, npm, or Vite. Start the Laravel development server with:

```bash
php artisan dev
```

`php artisan dev` starts the Laravel server, queue worker, and Vite, then automatically opens the application in your default browser once everything is ready. Pass `--no-open` to skip the browser:

```bash
php artisan dev --no-open
```

The CRUD generator reads the active database schema, so run the table migrations first. It creates a model, controller, Blade views under `resources/views`, and a resource route in `routes/web.php`. It derives basic validation rules from column types and nullability. Existing files are preserved.

The generated controller provides the resource actions and validates submitted values using rules derived from column types, nullability, and supported foreign keys. The views render the category dropdown, a checkbox for `is_active`, and the product list. The route manager adds a marked `products` resource block to `routes/web.php` and updates that block on later runs.

Use `--dry-run` to preview planned changes, or `--force` when you intend to overwrite generated model, controller, or view files.

## Limitations

The generator requires a single-column primary key. Composite foreign keys do not become Eloquent relationships, unique validation rules are not generated, and enum fields depend on metadata returned by the database driver. Route detection is limited to recognized resource and route-name patterns in `routes/web.php`.

See [Limitations](docs/limitations.md) for impacts and workarounds.

## Testing

Run the package test suite from the repository root:

```bash
vendor/bin/phpunit -c packages/devtools/phpunit.xml
```

## License

This package is licensed under [GNU Affero General Public License v3.0 only (AGPL-3.0-only)](https://www.gnu.org/licenses/agpl-3.0.html).

> [!WARNING]
> This package is licensed under AGPL-3.0-only. If you integrate this into a network-accessible application, you must comply with AGPL's copyleft requirements, which may include releasing your application's source code. For commercial/proprietary use, contact the maintainer for a separate license.

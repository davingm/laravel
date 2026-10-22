# Laravel Devtools

Development commands and a root `.laravel/` runtime for Laravel 11, 12, and 13. The package supports PHP 8.2 and newer, subject to the PHP requirement of the selected Laravel version.

Install it with Composer:

```bash
composer require --dev davingm/laravel-devtools
```

Composer asks to allow the `davingm/laravel-devtools` plugin. Once enabled, its Composer plugin copies the runtime to the application root, and Laravel package discovery registers the Artisan commands.

Available commands:

```bash
php artisan dev
php artisan devtools
php artisan make:all Product
php artisan make:controller Product --resource --model=Product
php artisan make:migration product
php artisan make:page about
php artisan make:dashboard --layout=sidebar
php artisan make:roles admin editor
php artisan crud:generate Product
```

The CRUD generator reads the active database schema, so run the table migrations first. It creates a model, controller, Blade views under `resources/views`, and a resource route in `routes/web.php`. It derives basic validation rules from column types and nullability. Existing files are preserved.

The interactive development panel also runs with `php artisan dev`. To expose the same entry point as `composer dev`, add this script to the root application's `composer.json`:

```json
"dev": [
  "Composer\\Config::disableProcessTimeout",
  "@php artisan dev"
]
```

Composer packages cannot add scripts to a consuming project's root manifest automatically. The CLI and the Artisan commands invoke the application's normal Laravel bootstrap; they do not depend on this repository's custom framework runtime.

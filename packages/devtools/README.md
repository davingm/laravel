# Laravel Devtools

Development commands and a root `.laravel/` runtime for Laravel 11, 12, and 13. The package supports PHP 8.2 and newer, subject to the PHP requirement of the selected Laravel version.

Install it with Composer:

```bash
composer require --dev davingm/laravel-devtools
```

Composer asks to allow the `davingm/laravel-devtools` plugin. Once enabled, its Composer plugin copies the runtime to the application root, and Laravel package discovery registers the Artisan commands.

Available commands:

```bash
php artisan devtools
php artisan crud:generate Product
```

The CRUD generator reads the active database schema, so run the table migrations first. It creates a model, controller, Blade views under `resources/views`, and a resource route in `routes/web.php`. Existing files are preserved.

To use the interactive development panel as `composer dev`, add this script to the root application's `composer.json`:

```json
"dev": [
  "Composer\\Config::disableProcessTimeout",
  "node .laravel/cli.js dev"
]
```

Composer packages cannot add scripts to a consuming project's root manifest automatically. The CLI and the Artisan commands invoke the application's normal Laravel bootstrap; they do not depend on this repository's custom framework runtime.

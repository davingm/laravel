# davingm/laravel

Laravel Framework modified by [davingm](https://github.com/davingm), powered by the [Laravel](https://laravel.com) framework.

## Create a New Project

```bash
composer create-project davingm/laravel nama-proyek
cd nama-proyek
```

After installation, the project is ready to use — including the `artisan` CLI tool.

## Development

Start the development environment (Laravel server + queue worker in one terminal):

```bash
artisan dev
```

The CLI will generate the Blade frontend manifest, then display the Laravel logo, port info, and color-coded output from both processes.

## Blade Frontend Mode

This starter includes Nuxt-inspired conventions without Vue. Keep route pages in `resources/views/pages`, use `resources/views/layouts/app.blade.php` as the shell, and render a page with a payload from a route:

```php
use App\Support\Frontend;

Route::get('/about', fn () => Frontend::render('about', [
	'title' => 'About',
	'description' => 'A server-rendered Blade page.',
]));
```

`@pageMeta` adds page metadata, `@payload` exposes the current state as JSON, and links with `data-navigate` use lightweight fetch navigation. The generated manifest and payload files live in `.davingm/cache`, which is ignored by Git.

Regenerate manually with:

```bash
artisan frontend:generate
```

## Artisan Commands

The `artisan` command is a shortcut for `php artisan`:

```bash
artisan migrate
artisan make:model User
artisan make:controller UserController
artisan route:list
artisan tinker
artisan <any-artisan-command>
```

## CLI Setup (manual, if needed)

The CLI is set up automatically on `composer create-project`. If you need to set it up manually:

```bash
cd .davingm
npm install
npm link
```

After linking, `artisan` will be available globally from the project directory.

## Requirements

- PHP >= 8.3
- Composer
- Node.js >= 18

## License

MIT

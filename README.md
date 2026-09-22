# davingm/laravel

Laravel starter project by [davingm](https://github.com/davingm), powered by the [Laravel](https://laravel.com) framework.

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

The CLI will display the Laravel logo, port info, and color-coded output from both processes.

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

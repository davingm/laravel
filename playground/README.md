# auto/laravel

Laravel framework project by [AutoLaravel](https://github.com/auto).

## Create a project

```bash
composer create-project auto/laravel app
cd app
```

The installer prepares the environment, runs database migrations, and builds the frontend assets.

## Development

For a fresh monorepo checkout, run once from `playground/`:

```bash
composer run setup
```

Start the custom development environment from `playground/`:

```bash
artisan dev
```

If the global launcher is stale or not available on `PATH`, run `node .laravel/cli.js dev` instead. This starts the Laravel server, queue worker, and Vite dev server.

Use `php artisan <command>` for Artisan commands, for example `php artisan route:list` or `php artisan test`.

## Frontend

Pages live in `src/pages` and shared layouts live in `src/layouts`. Run `npm run build` to build frontend assets.

## Requirements

- PHP >= 8.3
- Composer
- Node.js >= 20.19 or >= 22.12

## License

MIT

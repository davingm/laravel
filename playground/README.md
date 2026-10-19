# auto/laravel

Laravel framework project by [AutoLaravel](https://github.com/auto).

## Create a project

```bash
composer create-project auto/laravel app
cd app
```

The installer prepares the environment, runs database migrations, and builds the frontend assets.

## Development

Start the development environment:

```bash
composer run dev
```

Use `artisan <command>` for Artisan commands (no `php` prefix needed), for example `artisan route:list`, `artisan test`, or `artisan make:dashboard`.

## Frontend

Pages live in `src/pages` and shared layouts live in `src/layouts`. Run `npm run build` to build frontend assets.

## Requirements

- PHP >= 8.3
- Composer
- Node.js >= 18

## License

MIT

# davingm/laravel

Laravel framework project by [davingm](https://github.com/davingm), powered by the [Laravel](https://laravel.com) framework.

This repository is a monorepo: Composer packages live in `packages/`, while the Laravel framework project lives in `playground/`. The root Composer manifest bootstraps the framework from `playground/`, so users install a normal Laravel project rather than the whole monorepo.

## Create a project

```bash
composer create-project davingm/laravel app
cd app
```

Composer materializes the contents of `playground/` at the new project's root before installing dependencies. After installation, it generates the app key, runs migrations, and builds frontend assets.

## Develop in this repository

For monorepo development, install and run the Laravel project from `playground/`:

```bash
cd playground
composer install
npm ci --ignore-scripts
npm run build
artisan key:generate
artisan migrate
composer run dev
```

`composer run dev` starts the Laravel development environment. Use `artisan <command>` for any Artisan command (no need for `php artisan`), such as:

```bash
artisan about
artisan route:list
artisan test
artisan make:dashboard
```

The application source is under `playground/`. The Nuxt-inspired page conventions live in `playground/src/pages` and layouts in `playground/src/layouts`.

## Frontend

The project includes a server-rendered page frontend. Pages are in `src/pages` and shared layouts are in `src/layouts`, relative to `playground/`. Generated frontend manifest and payload files are stored in `playground/.davingm/cache` and ignored by Git.

Build frontend assets from `playground/`:

```bash
npm run build
```

## Composer package releases

Packages are maintained in `packages/<name>` and split to move for individual GitHub repositories on version tags. Repository setup, secrets, and release steps are documented in [docs/package-split-releases.md](docs/package-split-releases.md).

## CLI setup

The playground ships an `artisan` wrapper (`.auto/cli.js` + `artisan.cmd` on Windows) so you can run commands without typing `php`:

```bash
artisan make:model Product -m
artisan make:dashboard
artisan route:list
```

Composer installs a global launcher via `node .auto/setup.js` after `composer install`. If `artisan` is not found outside the project directory, run:

```bash
cd playground
node .auto/setup.js
```

Inside `playground/`, `artisan.cmd` works immediately in PowerShell and Command Prompt.

## Requirements

- PHP >= 8.3
- Composer
- Node.js >= 18

## License

GNU Affero General Public License v3.0

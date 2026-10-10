# davingm/laravel

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/davingm/laravel)

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
php artisan key:generate
php artisan migrate
composer run dev
```

`composer run dev` starts the Laravel development environment. You can also use `php artisan <command>` for any Artisan command, such as:

```bash
php artisan about
php artisan route:list
php artisan test
```

The application source is under `playground/`. The Nuxt-inspired page conventions live in `playground/src/pages` and layouts in `playground/src/layouts`.

## Frontend

The project includes a server-rendered page frontend. Pages are in `src/pages` and shared layouts are in `src/layouts`, relative to `playground/`. Generated frontend manifest and payload files are stored in `playground/.laravel/cache` and ignored by Git.

Build frontend assets from `playground/`:

```bash
npm run build
```

## Composer package releases

Packages are maintained in `packages/<name>` and split to move for individual GitHub repositories on version tags. The release process is being aligned with the Composer package names; see the [package split discussion](docs/discussion/package-split-releases.md) before attempting a release. Browse the [documentation index](docs/README.md) for feature guides and ongoing design notes.

## CLI setup (optional)

The `artisan` helper is part of the playground CLI. To link it for local use, run:

```bash
cd playground/.laravel
npm install
npm link
```

Then run `artisan` from the playground project directory. Standard `php artisan` commands also work without linking the helper.

## Requirements

- PHP >= 8.3
- Composer
- Node.js >= 20.19 or >= 22.12

## License

GNU Affero General Public License v3.0

<a href="https://github.com/davingm/laravel"><img width="830" src="./docs/public/image.png" alt="framework banner"></a>

# davingm/laravel

<p align="center">

<a href="https://deepwiki.com/davingm/laravel"><img src="/docs/public/deepwiki.svg" alt="Ask DeepWiki"></a>
<a href="https://packagist.org/packages/davingm/laravel"><img src="https://img.shields.io/packagist/dt/davingm/laravel" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/davingm/laravel"><img src="https://img.shields.io/packagist/v/davingm/laravel" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/davingm/laravel"><img src="https://img.shields.io/packagist/l/davingm/laravel" alt="License"></a>
<a href="php.net"><img src="https://img.shields.io/badge/php-v8.5-28CF8D?style=flat&colorA=777BB3" alt="NPM Version"></a>
 <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-18181B?style=flat&logo=laravel&logoColor=FF2D20" alt="Laravel"></a>

</p>

Laravel framework project by [davingm](https://github.com/davingm), powered by the [Laravel](https://laravel.com) framework.

This repository is a monorepo: the Composer runtime plugin and framework source live in `packages/laravel/`, while the Laravel application used for development and testing lives in `playground/`. The plugin creates the application's ignored `.laravel/` directory after autoload generation.

## Create a project

```bash
composer create-project davingm/laravel app
cd app
```

Composer materializes the contents of `playground/` at the new project's root. The internal runtime plugin is installed as a Composer dependency and creates the ignored `.laravel/` directory. The plugin must be published as `davingm/laravel-runtime` before external `create-project` installs can resolve it. After installation, the starter generates the app key, runs migrations, and builds frontend assets.

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

The application source is under `playground/`. Framework runtime and generator source is under `packages/laravel/.laravel/`; the generated `playground/.laravel/` copy is ignored. The Nuxt-inspired page conventions live in `playground/src/pages` and layouts in `playground/src/layouts`.

## Frontend

The project includes a server-rendered page frontend. Pages are in `src/pages` and shared layouts are in `src/layouts`, relative to `playground/`. Generated frontend manifest and payload files are stored in `playground/.laravel/cache` and ignored by Git.

Build frontend assets from `playground/`:

```bash
npm run build
```

## Composer package releases

Other packages are maintained in `packages/<name>`. The former package-split workflow is inactive; CI tests the playground through Apache on pushes and pull requests. Browse the [documentation index](docs/README.md) for feature guides and ongoing design notes.

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

## A special thank for Contributor

[![Contributors](https://contributors-img.web.app/image?repo=davingm/laravel&max=105&columns=15)](https://github.com/davingm/laravel/graphs/contributors)


## License

GNU Affero General Public License v3.0

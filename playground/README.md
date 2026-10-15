# davingm/laravel

Laravel starter project and framework tooling by [davingm](https://github.com/davingm).

> [!WARNING]
> This project is very new and still evolving. APIs, conventions, tooling, and documentation may change between releases. Evaluate it carefully and test it in an isolated environment before relying on it for production work.

## Create a project

```bash
composer create-project davingm/laravel app
cd app
```

The installer prepares the environment, runs database migrations, and builds frontend assets. Review your local `.env` before running setup again; migrations affect the configured database.

## Development

From a fresh monorepo checkout, run setup once from `playground/`:

```bash
composer run setup
```

The `.laravel/` runtime directory is installed by the `davingm/laravel-runtime` Composer plugin and ignored by Git. If it is removed, run `composer install` again to restore it.

Start the development environment from `playground/`:

```bash
composer run dev
```

This starts the Laravel server, queue worker, and Vite development server. Use `artisan <command>` for framework commands, for example `artisan route:list` or `artisan test`. If the global launcher is unavailable, run `node .laravel/cli.js <command>` from the project root.

## Frontend

Pages live in `src/pages` and shared layouts live in `src/layouts`. Run `npm run build` to build frontend assets.

## Requirements

- PHP `^8.3`
- Composer 2
- Node.js and npm compatible with the frontend dependencies

## License

Project-authored code is licensed under the [GNU Affero General Public License v3.0](https://github.com/davingm/laravel/blob/main/LICENSE) (`AGPL-3.0-only`). Dependencies retain their respective upstream licenses.

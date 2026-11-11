# Changelog


## 1.1.1

- Fix the auth starter's Vite and Tailwind entry points, publish dashboard icons to `public/icons`, and align dashboard links and views with the generated Laravel routes.

## 1.1.0

- Add `auth:install` command: scaffolds login, register, dashboard, profile, and user management with 3 default role-based users (administrator, operator, user) on a clean Laravel 12 project. Views use the built-in dashboard UI kit. Runs migration and seeder automatically.
- Automatically open the browser when `php artisan dev` starts the server. Pass `--no-open` to opt out, or set `NO_OPEN=1` in the environment.

## 1.0.0

- Add Composer plugin installation for the `.laravel` development runtime.
- Support Laravel 11, 12, and 13, subject to the PHP requirement of each Laravel version.
- Add Artisan commands for the development panel, interactive Devtools menu, CRUD generation, and common scaffolding tasks.
- Generate CRUD files from the active database schema using conventional Laravel application paths.
- Automatically open the browser when `php artisan dev` starts the server. Use `--no-open` to opt out.
- Add `auth:install` command: scaffolds login, register, dashboard, profile, and user management with 3 default roles (administrator, operator, user).

All notable user-facing changes to Laravel Devtools are documented here.

This project follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [0.1.0-alpha] - 2026-11-10

### Added

- Initial alpha release of the Auto CRUD generator through `./artisan crud:generate`.
- Artisan development commands: `dev`, `devtools`, `make:all`, `make:controller`, `make:migration`, `make:page`, and `make:dashboard`.
- Role middleware generation through `make:roles`.
- Generated CRUD models, resource controllers, index/create/edit Blade views, and a resource route.
- Single-column foreign keys generate relationship methods, basic foreign-key validation, and dropdown data for forms.
- Boolean columns render as checkboxes in generated forms and as Yes/No values in index views.
- Marked Auto CRUD route blocks are updated on subsequent runs, avoiding duplicate generated resource routes.
- A `--dry-run` option to preview generated files and route changes, and a `--force` option to overwrite existing generated files.
- Known schema and framework compatibility limitations are documented in [Limitations](docs/limitations.md).

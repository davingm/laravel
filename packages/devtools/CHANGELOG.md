# Changelog

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

# Upgrading from `devtools:crud` (legacy) to `crud:generate`

The current CRUD generator is available as `crud:generate`. The old `devtools:crud` command name is still registered as an alias for compatibility.

## Breaking change: `--role`

The legacy command accepted `--role` to protect generated resource routes with a role alias. The current `crud:generate` command does not accept `--role` and does not add role middleware to generated routes.

Before:

```bash
./artisan devtools:crud Product --role=admin
```

After:

```bash
./artisan crud:generate Product
```

If the resource needs authorization, add the appropriate middleware to the routes and policies in your application. Review the generated route and controller before exposing the resource.

## Command changes

| Legacy usage | Current usage |
| --- | --- |
| `./artisan devtools:crud Product` | `./artisan crud:generate Product` |
| `./artisan devtools:crud Product --role=admin` | `./artisan crud:generate Product`, then configure authorization in the application |

The current command accepts `--dry-run` and `--force`. It skips existing model, controller, and view files unless `--force` is supplied. Route blocks marked for Auto CRUD are updated when the command runs; an existing manually declared route may cause route registration to be skipped.

There is no automatic migration of generated files. Review existing models, controllers, views, route declarations, and authorization before running the new command. Use `--dry-run` to preview its planned file and route changes.

## Deprecation Status

As of v0.1.0-alpha, `devtools:crud` is silently aliased to `crud:generate`. No deprecation warning is emitted at runtime yet. This will be added in a future release.

If you are starting a new project, use `crud:generate` directly. The `devtools:crud` alias is provided for backward compatibility only and may be removed in a future version.


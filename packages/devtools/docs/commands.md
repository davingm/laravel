# Command Reference

## `./artisan crud:generate`

```text
crud:generate {model} {--dry-run} {--force}
```

The required `model` argument may be a model name such as `Product` or a table name such as `products`. The command normalizes it to a table name, then inspects that table on the active database connection.

### Options

| Option | Description |
| --- | --- |
| `--dry-run` | Preview generated files and the route block without writing changes. |
| `--force` | Overwrite existing model, controller, and view files. Auto CRUD route blocks are updated whenever the command runs. |

The options cannot be combined. Existing model, controller, and view files are skipped unless `--force` is specified. A manually declared resource route can cause route registration to be skipped.

### Examples

Generate a CRUD for the `products` table:

```bash
./artisan crud:generate Product
```

Preview the operation:

```bash
./artisan crud:generate products --dry-run
```

Overwrite generated files that already exist:

```bash
./artisan crud:generate Product --force
```

The `devtools:crud` command name remains available as a silent alias. New projects should use `crud:generate`; see [Upgrading](../UPGRADING.md).

### Generated output

The command reports created, updated, skipped, and failed files, plus route registration status. A successful run ends with a summary in this form:

```text
Summary:
  N files created
  N files updated
  N files skipped
  0 errors
```

With `--dry-run`, the summary reports files that would be created or skipped and states that no files were written. Route status messages report whether the route block would be added, was added or updated, or was skipped.

### Exit status

- `0`: command completed without generation errors.
- `1`: invalid input, incompatible options, schema inspection failure, or one or more generation errors.

The command can report errors for individual outputs while processing the remaining steps; any such failure produces exit status `1`.


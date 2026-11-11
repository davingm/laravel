# Architecture

## Generation pipeline

```text
Active database
      |
      v
SchemaInspector
      |
      v
CrudDefinition + column and relationship metadata
      |
      +----------------+----------------+----------------+
      v                v                v                v
ModelGenerator  ControllerGenerator  ViewGenerator   RouteStub
      |                |                |                |
      v                v                v                v
ModelWriter     ControllerWriter    ViewWriter      RouteManager
      |                |                |                |
      +----------------+----------------+----------------+
                              |
                              v
                    Application files and routes
```

## Components

- **SchemaInspector** asks Laravel's schema builder for the selected table's indexes, columns, and foreign keys.
- **CrudDefinition** carries table, primary key, column, and relationship metadata used by the generators.
- **ModelGenerator** builds the Eloquent model, fillable fields, supported casts, and single-column relationships.
- **ControllerGenerator** builds resource actions, basic validation rules, and data needed for supported relationship dropdowns.
- **ViewGenerator** builds index, create, and edit Blade templates from the table metadata.
- **RouteStub** renders a marked resource route block. **RouteManager** detects existing marked blocks or recognized manual routes and safely updates the route file.
- **Writers** handle filesystem output for generated models, controllers, and views. The command coordinates these writers, dry-run previews, overwrite behavior, and reporting.

## Why inspect the active database?

The generator reads the schema from Laravel's active database connection. This reflects the database the application is currently configured to use, including tables that may not be represented by migration files available in the current checkout. It does not parse migration source code.

Run the application's migrations against the intended development database before generating a CRUD. Schema metadata is database-driver dependent; see [Limitations](limitations.md).

## Why generators and writers are separate

Generators build file content from metadata, while writers handle paths and filesystem changes. Keeping these responsibilities separate allows generator output and writer behavior to be tested independently, and lets the command preview generated content without writing it.

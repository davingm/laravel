# Limitations

The generator uses metadata returned by Laravel's schema builder for the active database connection. Driver behavior and schema design affect which features can be inferred.

## Keys and relationships

### Composite primary keys

Composite primary keys are not supported. Model, controller, view, and route generation requires one primary key and fails when a table has a composite primary key.

**Workaround:** use a single-column primary key for tables you want to generate CRUD for, or implement that resource manually.

### Composite foreign keys

Composite foreign keys are retained in schema metadata, but they do not produce a single Eloquent relationship. Related dropdowns and the corresponding foreign-key validation rule are skipped for those constraints, with warnings where available.

**Workaround:** implement the relationship, form field, and validation rules manually.

### Dropdown labels

A generated relationship dropdown tries the related record's `name`, `title`, or `label` property, in that order. If none is available, it displays the referenced key value.

**Workaround:** provide one of those label columns or customize the generated view.

## Validation and column types

### Unique validation

The generator does not create `unique` validation rules. It derives basic rules from column types, nullability, and supported single-column foreign keys; uniqueness metadata is not used to build rules.

**Workaround:** add a unique rule to the generated controller or move validation into a Form Request.

### Enum metadata

Enum columns become a select when the database driver returns supported allowed-value metadata. Without that metadata, the generated field falls back to a text input.

**Workaround:** replace the generated input with a select containing the application's allowed values.

### Driver-specific metadata

Laravel drivers may return different type names, defaults, lengths, and optional flags. Column length is used only when it is present in the metadata returned by the active driver.

**Workaround:** inspect the generated validation rules and fields, then adjust them for the database and application requirements.

## Model behavior

### Soft deletes

The generator does not enable Eloquent soft deletes or generate soft-delete-specific behavior.

**Workaround:** add the `SoftDeletes` trait and configure the generated CRUD behavior manually if the model uses soft deletes.

## Routes

### Route detection

Manual route detection recognizes `Route::resource(...)` and route names matching the resource name. It does not analyze `Route::controller()->group()`, aliased Route facades, or routes stored in other files.

**Workaround:** inspect `routes/web.php` after generation and resolve duplicate or missing routes manually.

### API routes

The route manager writes only to `routes/web.php`; it does not update `routes/api.php`.

**Workaround:** add API routes and an API-specific controller manually.

## Laravel versions and errors

### Laravel 12 and 13

Integration tests currently verify Laravel 11 through Orchestra Testbench 9. Laravel 12 and 13 have not been verified by this suite.

**Workaround:** validate the package against your Laravel version before adopting it in production. A CI matrix using Testbench 9, 10, and 11 is recommended as post-MVP work.

### Schema inspection exceptions

There is no dedicated `SchemaInspectionException` contract yet. Schema inspection reports errors using standard exception types such as `InvalidArgumentException`, `RuntimeException`, and metadata-related exceptions.

**Workaround:** handle the command's non-zero exit status and inspect its error message. Avoid coupling application code to a specific exception type.

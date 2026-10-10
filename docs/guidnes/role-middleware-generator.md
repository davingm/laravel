# Panduan Role Middleware Generator

Panduan ini menjelaskan cara membuat middleware role otomatis dengan command `make:roles` dan cara menggunakannya bersama `crud:generate --role`.

> Jalankan semua perintah dari folder `playground/`.

## 1. Buat middleware role otomatis

Command berikut membuat file middleware untuk satu atau beberapa role sekaligus:

```bash
php artisan make:roles admin author editor
```

Hasilnya akan membuat file berikut di `app/Http/Middleware/`:

```text
app/Http/Middleware/EnsureUserIsAdminMiddleware.php
app/Http/Middleware/EnsureUserIsAuthorMiddleware.php
app/Http/Middleware/EnsureUserIsEditorMiddleware.php
```

Setiap file middleware dibuat dari stub `.laravel/role/middleware.stub` dan berisi pengecekan role user seperti berikut:

```php
$user = $request->user();
$hasRole = $user && $user->role === 'admin';

if (! $hasRole) {
    abort(403, 'Unauthorized.');
}
```

Artinya, stub default mengasumsikan model `User` punya kolom `role` dan membandingkan nilainya dengan role yang sesuai.

## 2. Alias middleware otomatis terdaftar

Generator juga mendaftarkan alias middleware ke bootstrap secara otomatis agar bisa dipakai di route dengan nama berikut:

```php
role.admin
role.author
role.editor
```

Alias ini otomatis ditambahkan ke `bootstrap/app.php` dalam blok `withMiddleware(...)` untuk middleware alias.

## 3. Gunakan di route

Setelah middleware dibuat dan alias terdaftar, route dapat memakai middleware seperti ini:

```php
Route::middleware(['auth', 'role.admin'])->group(function () {
    Route::get('/dashboard', fn () => 'Admin dashboard');
});
```

Untuk route group yang lebih umum, Anda cukup menambahkan alias `role.admin` di middleware array.

## 4. Gunakan bersama CRUD generator

Generator CRUD dapat membuat route yang otomatis dibungkus dengan auth + role middleware jika Anda menambahkan opsi `--role`.

Contoh:

```bash
php artisan crud:generate Product --role=admin
```

Hasil yang masuk ke `app/routes/web.php` akan seperti ini:

```php
// CRUD Routes for Product (Role: admin)
Route::middleware(['auth', 'role.admin'])->group(function () {
    Route::resource('products', \App\Http\Controllers\ProductController::class);
});
```

Perhatikan:

- `auth` selalu masuk di depan
- `role.admin` masuk setelah `auth`
- route tetap aman dan idempotent, tidak menimpa route yang sudah ada

## 5. Cek hasilnya

### Cek file middleware
```bash
ls app/Http/Middleware
```

### Cek route yang terdaftar
```bash
php artisan route:list --path=products
```

Pastikan route product muncul dengan middleware:

- `auth`
- `role.admin`

### Cek test otomatis
```bash
php artisan test --filter='CrudGenerateCommandTest|MakeRolesCommandTest'
```

Semua test harus PASS.

## 6. Catatan penting

- Generator default untuk `make:roles` mengikuti stub `role` dan asumsikan kolom `user->role`.
- Jika nantinya Anda ingin memakai `Spatie/laravel-permission`, stub dan pengecekan role dapat disesuaikan lagi tanpa mengubah command utama.
- Generator ini dibuat terpisah dari `crud:generate`, jadi fitur CRUD yang sudah ada tidak diubah secara besar-besaran.

      # Panduan Rebranding AutoLaravel

Panduan ini merangkum perubahan nama dari `davingm` menjadi `AutoLaravel` untuk namespace PHP dan `auto` untuk nama package serta komponen CLI.

## 1. Nama package Composer

Project aplikasi menggunakan nama `auto/laravel` pada manifest root dan `playground/composer.json`. Package autentikasi menggunakan nama `auto/auth`.

Autoload package auth memetakan namespace `AutoLaravel\Auth\` ke folder `src/`, dan provider Laravel didaftarkan sebagai `AutoLaravel\Auth\AuthServiceProvider`. Implementasi PHP package berada di `packages/auth/src/`.

Saat membuat project baru melalui Composer, gunakan nama package baru:

```bash
composer create-project auto/laravel app
```

## 2. Namespace dan integrasi package auth

Namespace class package auth menggunakan prefix berikut:

```php
namespace AutoLaravel\Auth;
```

Subnamespace console dan controller mengikuti struktur class-nya, misalnya `AutoLaravel\Auth\Console` dan `AutoLaravel\Auth\Http\Controllers`.

Provider dan command installer memakai nama konfigurasi serta view namespace berikut:

```text
config/auto-auth.php
auto-auth::login
auto-auth::dashboard
auto-src::layouts.app
```

## 3. CLI dan direktori runtime

Source CLI, cache frontend, dan flag preview kini berada di `.laravel/`. Script Composer, launcher, dan perintah Artisan menggunakan direktori ini. Output CLI memakai label `[laravel]`.

Nama Composer `auto/laravel`, namespace `AutoLaravel`, variabel environment preview `AUTO_PREVIEW`, serta API JavaScript `window.__AUTO_LARAVEL__` dan event `auto:navigated` tetap dipertahankan untuk menjaga kompatibilitas.

## 4. Nama command Artisan

Tidak ditemukan command Artisan dengan prefix `davingm:` pada implementasi saat rebranding dilakukan. Karena itu, nama command yang sudah ada tetap dipertahankan, antara lain:

```text
crud:generate
make:roles
auth:install
```

Generator framework di `playground/.laravel/generators/` didaftarkan melalui bootstrap Laravel. Jalankan perintah dari folder `playground/`.

## 5. Verifikasi setelah perubahan

Dari root repository, masuk ke folder aplikasi:

```bash
cd playground
```

Bangun ulang autoload Composer dan asset frontend, lalu jalankan test:

```bash
composer dump-autoload
npm run build
php artisan test
```

Untuk memeriksa manifest package auth dari root repository:

```bash
composer validate --no-check-publish --no-check-lock --working-dir=packages/auth
```

## Catatan

- Gunakan `auto/laravel` dan `auto/auth` untuk nama package baru.
- Jangan mengembalikan referensi direktori `.davingm/` atau `.auto/`, konfigurasi `davingm-auth`, atau namespace PHP `Davingm\Auth` pada kode baru.
- Project yang sudah disalin sebelum rebranding mungkin masih memiliki direktori `.davingm/` atau `.auto/`; cache lama dapat dibuat ulang oleh aplikasi setelah source dipindah ke `.laravel/`.
- File aplikasi utama berada di `playground/`; perubahan bootstrap package dan validasi materializer berada di root repository.

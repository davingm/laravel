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

Direktori internal `.davingm/` pada aplikasi telah diganti menjadi `.auto/`. Script Composer dan CLI menggunakan lokasi baru, termasuk cache frontend dan flag preview.

Variabel environment preview yang digunakan sekarang adalah `AUTO_PREVIEW`. Kode JavaScript aplikasi menyediakan `window.__AUTO_LARAVEL__` dan mengirim event `auto:navigated` setelah navigasi.

## 4. Nama command Artisan

Tidak ditemukan command Artisan dengan prefix `davingm:` pada implementasi saat rebranding dilakukan. Karena itu, nama command yang sudah ada tetap dipertahankan, antara lain:

```text
crud:generate
make:roles
auth:install
```

Command aplikasi di `app/Console/Commands/` didaftarkan melalui discovery Laravel. Jalankan perintah dari folder `playground/`.

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
- Jangan mengembalikan referensi direktori `.davingm/`, konfigurasi `davingm-auth`, atau namespace PHP `Davingm\Auth` pada kode baru.
- Project yang sudah disalin sebelum rebranding mungkin masih memiliki direktori `.davingm/` atau memakai `DAVINGM_PREVIEW`; sesuaikan file dan environment tersebut dengan nama baru.
- File aplikasi utama berada di `playground/`; perubahan bootstrap package dan validasi materializer berada di root repository.
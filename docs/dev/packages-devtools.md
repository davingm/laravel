# Package `packages/devtools`

`packages/devtools/` adalah package Composer untuk memasang alat pengembangan pada aplikasi Laravel standar. Target framework-nya Laravel 11, 12, dan 13. Kode harus memakai API Laravel yang tersedia pada semua versi tersebut dan tidak boleh mengandalkan perubahan khusus di `playground/` atau runtime framework `packages/laravel/`.

## Instalasi dan struktur

Package mendaftarkan Composer plugin yang menyalin isi `packages/devtools/.laravel/` ke direktori `.laravel/` aplikasi. Laravel package discovery kemudian mendaftarkan `DevtoolsServiceProvider`, yang memasang command Artisan.

Composer meminta izin untuk mengaktifkan plugin. Aplikasi pemakai perlu mengizinkan `davingm/laravel-devtools` pada `config.allow-plugins`. Package tidak dapat menambahkan izin atau script Composer ke manifest root aplikasi secara otomatis.

Source PHP package berada di `src/`, runtime CLI/stub berada di `.laravel/`, dan contoh instalasi Laravel 12 berada di `examples/laravel12/`. Folder runtime lokal aplikasi adalah hasil instalasi; ubah source package, bukan salinannya.

## Command

- `php artisan dev` menjalankan panel development, server Laravel, queue worker, dan Vite, lalu otomatis membuka browser ketika semua siap. `php artisan dev --no-tui` menjalankan mode output biasa. `php artisan dev --no-open` menonaktifkan auto-open browser.
- `php artisan devtools` membuka menu interaktif untuk semua generator package.
- `php artisan make:migration product` membuat migration `create_products_table`.
- `php artisan make:all Product`, `make:controller`, `make:page`, `make:dashboard`, dan `make:roles` tersedia seperti generator framework dengan path Laravel standar.
- `php artisan crud:generate Product` membaca tabel `products` dari koneksi database aktif dan menghasilkan model, controller, Blade view di `resources/views/products/`, validasi dasar dari tipe/nullability kolom, serta route resource di `routes/web.php`.
- Generator tidak membuat atau menjalankan migration. Jalankan migration lebih dulu. File yang telah ada dilewati.
- `php artisan auth:install` memasang auth starter lengkap (login, register, dashboard, profil, manajemen user) pada project Laravel 12 yang bersih. Membutuhkan project yang belum memiliki controller auth, views auth, atau migration role. Gunakan `--force` untuk menimpa file yang sudah ada. UI auth dilayani dari `public/css/devtools-auth.css` dan `public/js/devtools-auth.js`, serta ikon dari `public/icons/`; halaman auth tidak membutuhkan Node.js, npm, atau Vite. Menyediakan 3 user default: `administrator`, `operator`, dan `user` via `AuthSeeder` (bukan factory).

Verifikasi package dilakukan pada contoh Laravel 12. Contoh Laravel 11 dan 13 dapat diuji terpisah.
- `composer dev` dapat diteruskan ke `@php artisan dev` melalui script di `composer.json` root.

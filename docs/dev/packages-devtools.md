# Package `packages/devtools`

`packages/devtools/` adalah package Composer untuk memasang alat pengembangan pada aplikasi Laravel standar. Target framework-nya Laravel 11, 12, dan 13. Kode harus memakai API Laravel yang tersedia pada semua versi tersebut dan tidak boleh mengandalkan perubahan khusus di `playground/` atau runtime framework `packages/laravel/`.

## Instalasi dan struktur

Package mendaftarkan Composer plugin yang menyalin isi `packages/devtools/.laravel/` ke direktori `.laravel/` aplikasi. Laravel package discovery kemudian mendaftarkan `DevtoolsServiceProvider`, yang memasang command Artisan.

Composer meminta izin untuk mengaktifkan plugin. Aplikasi pemakai perlu mengizinkan `davingm/laravel-devtools` pada `config.allow-plugins`. Package tidak dapat menambahkan izin atau script Composer ke manifest root aplikasi secara otomatis.

Source PHP package berada di `src/`, runtime CLI/stub berada di `.laravel/`, dan contoh instalasi Laravel 12 berada di `examples/laravel12/`. Folder runtime lokal aplikasi adalah hasil instalasi; ubah source package, bukan salinannya.

## Command

- `php artisan devtools` membuka menu interaktif untuk generator CRUD, daftar route, dan pembersihan cache.
- `php artisan crud:generate Product` membaca tabel `products` dari koneksi database aktif dan menghasilkan model, controller, Blade view di `resources/views/products/`, serta route resource di `routes/web.php`.
- Generator tidak membuat atau menjalankan migration. Jalankan migration lebih dulu. File yang telah ada dilewati.
- Untuk menjalankan panel CLI sebagai `composer dev`, aplikasi perlu menambahkan `node .laravel/cli.js dev` ke script `dev` di `composer.json` root.

Verifikasi package dilakukan pada contoh Laravel 12. Contoh Laravel 11 dan 13 dapat diuji terpisah.

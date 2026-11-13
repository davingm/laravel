# Package `packages/devtools`

`packages/devtools/` adalah package Composer untuk memasang alat pengembangan pada aplikasi Laravel standar. Target framework-nya Laravel 11, 12, dan 13. Kode harus memakai API Laravel yang tersedia pada semua versi tersebut dan tidak boleh mengandalkan perubahan khusus di `playground/` atau runtime framework `packages/laravel/`.

## Instalasi dan struktur

Package mendaftarkan Composer plugin yang menyalin isi `packages/devtools/.laravel/` ke direktori `.laravel/` aplikasi. Laravel package discovery kemudian mendaftarkan `DevtoolsServiceProvider`, yang memasang command Artisan.

Composer meminta izin untuk mengaktifkan plugin. Aplikasi pemakai perlu mengizinkan `davingm/laravel-devtools` pada `config.allow-plugins`. Package tidak dapat menambahkan izin atau script Composer ke manifest root aplikasi secara otomatis.

Source PHP package berada di `src/`, runtime CLI/stub berada di `.laravel/`, dan contoh instalasi Laravel 12 berada di `examples/laravel12/`. Folder runtime lokal aplikasi adalah hasil instalasi; ubah source package, bukan salinannya.

Source preset peminjaman di `src/stubs/presets/peminjaman/` mengikuti kelompok Laravel: `app/Http/Controllers`, `app/Http/Middleware`, `app/Models`, `database/migrations`, `database/seeders`, `resources/css`, `resources/js`, `resources/views/pages/{tools,categories,loans,returns,users}`, dan `routes/`. Semua view aplikasi berkumpul langsung di bawah `resources/views/pages/`; tidak ada wrapper `peminjaman/`. Halaman login/register tetap di `resources/views/auth/`, halaman daftar akun auth di `pages/accounts`, dan dashboard memakai satu `layouts/dashboard.blade.php` serta komponen kerangka di `resources/views/components/app/`.

## Command

- `php artisan dev` menjalankan panel development, server Laravel, queue worker, dan Vite, lalu otomatis membuka browser ketika semua siap. `php artisan dev --no-tui` menjalankan mode output biasa. `php artisan dev --no-open` menonaktifkan auto-open browser.
- `php artisan devtools` membuka menu interaktif untuk semua generator package.
- `php artisan make:migration product` membuat migration `create_products_table`.
- `php artisan make:all Product`, `make:controller`, `make:page`, `make:dashboard`, dan `make:roles` tersedia seperti generator framework dengan path Laravel standar.
- `php artisan crud:generate Product` membaca tabel `products` dari koneksi database aktif dan menghasilkan model, controller, Blade view di `resources/views/pages/products/`, validasi dasar dari tipe/nullability kolom, serta route resource di `routes/web.php`.
- `php artisan make:page about` membuat view di `resources/views/pages/about.blade.php`; nama view boleh memakai subfolder seperti `admin/users`.
- Generator tidak membuat atau menjalankan migration. Jalankan migration lebih dulu. File yang telah ada dilewati.
- `php artisan auth:install` memasang auth starter lengkap (login, register, dashboard, profil, manajemen user) pada project Laravel 12 yang bersih. Halaman dashboard, profil, dan user diletakkan di `resources/views/pages/`; login dan register tetap di `resources/views/auth/`. UI dilayani dari `public/css/devtools-auth.css` dan `public/js/devtools-auth.js`, serta ikon dari `public/icons/`; tidak membutuhkan Node.js, npm, atau Vite. Menyediakan 3 user default: `administrator`, `operator`, dan `user` via `AuthSeeder`.
- `php artisan devtools:install --preset=peminjaman` hanya berjalan setelah starter `auth:install` terpasang. Perintah menyalin halaman langsung ke `resources/views/pages/{tools,categories,loans,returns,users}` dan komponen Blade kecil ke `resources/views/components/ui/`, menjalankan migration dan seeder, lalu menambahkan menu role-aware ke sidebar auth yang sama. Migration item secara eksplisit menghubungkan `peminjaman_id` ke tabel `peminjamans` (tidak mengandalkan pluralisasi nama Indonesia). Setelah pemasangan, command menjalankan smoke checks berwarna untuk tabel, routes, aset UI, dan insert item dalam transaksi yang di-rollback. Komponen UI mencakup form, field, input, select, textarea, dan button dengan CSS native di `public/css/peminjaman.css`; tidak membutuhkan Vite atau npm. Header dan sidebar berwarna hitam pekat, workspace dashboard `#080808`, sedangkan permukaan tabel, form, dan panel memakai `#0A0A0A`; sudut kiri workspace membulat. Admin dan operator dapat menambah alat/kategori; perubahan atau penghapusan inventaris tetap khusus admin. Operator dapat melihat seluruh riwayat peminjaman, termasuk yang disetujui dengan status `Dipinjam`, memproses persetujuan/pengembalian, tetapi tidak mengelola akun pengguna. User dapat mengajukan beberapa alat beserta jumlahnya dalam satu pengajuan; stok diperiksa lagi saat operator menyetujui.

Verifikasi package dilakukan pada contoh Laravel 12. Contoh Laravel 11 dan 13 dapat diuji terpisah.
- `composer dev` dapat diteruskan ke `@php artisan dev` melalui script di `composer.json` root.

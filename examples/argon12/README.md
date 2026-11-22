# davingm/argon

Starter aplikasi peminjaman berbasis Laravel 12. Antarmuka dashboard dan komponennya memakai source Argon Dashboard v1.0.0 yang tersedia di `argon-dashboard-v1.0.0`; source tersebut disalin langsung sebagai aset publik dan menjadi dasar layout Blade. Backend peminjaman mengikuti controller, model, route, migration, dan alur preset `packages/devtools/src/stubs/presets/peminjaman`. Halaman dashboard generik dari preset tidak digunakan.

## Persyaratan

- PHP 8.2 atau lebih baru.
- Laravel Framework 12.
- Composer 2.

Composer constraint `^8.2` menerima PHP 8.2 dan versi 8.x yang lebih baru; paket tidak menaikkan persyaratan minimum ke PHP 8.3 atau 8.5. Laravel dibatasi ke major 12.

## Instalasi

```sh
composer create-project davingm/argon nama-aplikasi
cd nama-aplikasi
php artisan serve
```

Composer menyalin `.env`, membuat application key, membuat SQLite database lokal, menjalankan migration, lalu mengisi data contoh. Akun contoh menggunakan password `password`: `admin@example.com` (administrator), `operator@example.com` (operator), dan `user@example.com` (peminjam). Ganti atau hapus akun contoh sebelum aplikasi dipakai bersama. Atur koneksi database pada `.env` bila tidak menggunakan SQLite. Pendaftaran akun publik membuat role `user`.

Rute fitur peminjaman berada di `/peminjaman/tools`, `/peminjaman/loans`, `/peminjaman/returns`, dan `/peminjaman/reports`. Navigasi, autentikasi, dan halaman fitur memakai aset Argon yang disimpan lokal di `public/assets`; dashboard tidak memerlukan npm atau Vite. Font Open Sans mengikuti tautan Google Fonts yang disertakan template asli.

## Lisensi

Kode yang ditulis untuk package ini dilisensikan di bawah AGPL-3.0-only; lihat [`LICENSE`](LICENSE). Template dan aset Argon Dashboard upstream tetap berlisensi MIT dan atribusinya dipertahankan di `public/assets/ARGON-LICENSE-MIT.md`. Metadata package tidak mengubah lisensi karya pihak ketiga.

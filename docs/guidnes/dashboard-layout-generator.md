# Panduan Dashboard Layout Generator

Panduan ini menjelaskan cara membuat layout Blade sidebar atau navbar menggunakan command `make:dashboard` di aplikasi `playground`.

> Jalankan semua command dari folder `playground/`. Generator tidak menimpa `src/layouts/app.blade.php` yang sudah ada.

## 1. Pilih tipe layout

Generator mendukung dua pilihan:

- `sidebar`: navigasi di sisi kiri dan area konten di sebelah kanan.
- `navbar`: navigasi horizontal di bagian atas dan area konten di bawahnya.

Jika opsi `--layout` tidak diberikan, generator menggunakan `sidebar`.

## 2. Buat layout sidebar

Dari root repository, masuk ke aplikasi:

```bash
cd playground
```

Jalankan command tanpa opsi untuk memakai layout sidebar:

```bash
artisan make:dashboard
```

Pilihan default yang sama juga dapat ditulis secara eksplisit:

```bash
artisan make:dashboard --layout=sidebar
```

## 3. Buat layout navbar

Untuk memilih navigasi horizontal di bagian atas, gunakan:

```bash
artisan make:dashboard --layout=navbar
```

Layout hasil generate disimpan di:

```text
src/layouts/app.blade.php
```

File tersebut menjadi layout Blade utama karena `src/` adalah root view aplikasi. Kedua pilihan menyediakan struktur HTML, styling Tailwind dasar dengan navigasi abu-abu dan area konten putih, serta `@yield('content')`.

## 4. Perilaku file yang sudah ada

Jika `src/layouts/app.blade.php` sudah ada, command menampilkan warning dan melewati proses penulisan. File tidak diubah, termasuk saat opsi `--layout` yang berbeda diberikan.

Karena aplikasi ini sudah memiliki layout pada lokasi tersebut, menjalankan command di checkout ini akan melewatinya. Pilih sidebar atau navbar sebelum membuat layout pada project baru; tinjau dan ubah layout yang ada secara manual bila ingin mengganti desainnya.

## 5. Gunakan layout pada halaman CRUD

Stub CRUD menggunakan `@extends('layouts.app')` agar halaman yang dihasilkan memakai layout tersebut:

```text
stubs/crud/view_index.stub  → src/pages/<resource>/index.blade.php
stubs/crud/view_form.stub   → src/pages/<resource>/create.blade.php
stubs/crud/edit.stub        → src/pages/<resource>/edit.blade.php
```

Jalankan generator CRUD seperti biasa setelah schema database siap:

```bash
artisan crud:generate Product
```

File CRUD yang sudah ada tetap dilewati dan tidak ditimpa.

## 6. Build asset dan jalankan test

Utility Tailwind pada stub layout ikut dipindai saat build asset. Jalankan dari folder `playground/`:

```bash
npm run build
artisan test --filter=MakeDashboardCommandTest
```

Feature test memeriksa default sidebar, opsi navbar, dan perilaku agar layout yang sudah ada tidak ditimpa. Untuk menjalankan seluruh test aplikasi:

```bash
artisan test
```

## Catatan

- Nama opsi yang valid adalah `sidebar` dan `navbar`.
- Generator membuat folder `src/layouts/` bila belum tersedia.
- Layout mempertahankan hook metadata, asset Vite, payload frontend, dan elemen `#page-view` yang digunakan navigasi aplikasi.
- Command di folder `app/Console/Commands/` didaftarkan melalui discovery Laravel.
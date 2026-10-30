# Panduan Dashboard Layout Generator

Panduan ini menjelaskan cara membuat layout Blade sidebar atau navbar menggunakan command `make:dashboard`.

> Jalankan semua command dari root aplikasi, yaitu folder yang berisi `composer.json` dan `bootstrap/app.php`. Layout default `src/layouts/default.blade.php` tidak diubah oleh generator.

## 1. Pilih tipe layout

Generator mendukung dua pilihan:

- `sidebar`: navigasi di sisi kiri dan area konten di sebelah kanan.
- `navbar`: navigasi horizontal di bagian atas dan area konten di bawahnya.

Jika opsi `--layout` tidak diberikan, generator menggunakan `sidebar`.

## 2. Buat layout sidebar

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
src/layouts/{sidebar|navbar}.blade.php
```

File tersebut menjadi layout bernama `sidebar` yang dipilih oleh route dashboard. Layout default `src/layouts/default.blade.php` tetap menjadi layout halaman lain. Kedua pilihan menyediakan dokumen HTML, styling Tailwind dasar, metadata, asset, payload, dan `@yield('content')`.

## 4. Perilaku file yang sudah ada

Jika file layout bernama pilihan sudah ada, command menampilkan warning dan melewati proses penulisan. File tersebut tidak diubah.

## 5. Gunakan layout pada halaman CRUD

Halaman dashboard dan stub CRUD memakai format satu file Blade, sehingga dirender oleh entry aplikasi. Resource CRUD memakai layout default; route dashboard memilih layout yang dibuat generator:

```text
.laravel/crud/view_index.stub  → src/pages/<resource>/index.blade.php
.laravel/crud/view_form.stub   → src/pages/<resource>/create.blade.php
.laravel/crud/edit.stub        → src/pages/<resource>/edit.blade.php
```

Jalankan generator CRUD seperti biasa setelah schema database siap:

```bash
artisan crud:generate Product
```

File CRUD yang sudah ada tetap dilewati dan tidak ditimpa.

## 6. Build asset dan jalankan test

Utility Tailwind pada stub layout ikut dipindai saat build asset. Jalankan dari root aplikasi:

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
- Layout mempertahankan metadata, asset Vite, payload frontend, dan elemen `#page-view` yang digunakan navigasi aplikasi.
- Generator PHP ada di `.laravel/generators/` dan didaftarkan oleh bootstrap Laravel.

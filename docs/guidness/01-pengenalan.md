# 1. Pengenalan

Selamat datang di AutoLaravel, framework berbasis Laravel untuk membangun aplikasi web dengan Blade, pola halaman berbasis file, dan tooling frontend yang sudah disiapkan.

## Apa yang disediakan?

AutoLaravel menggabungkan kemampuan aplikasi Laravel dengan beberapa konvensi siap pakai:

- **Backend Laravel:** routing, middleware, controller, model Eloquent, database, validasi, dan Artisan.
- **Halaman Blade:** halaman berada di `src/pages/` dan dapat memperoleh route GET dari struktur file.
- **Layout dan komponen:** elemen tampilan bersama berada di `src/layouts/` dan `src/components/`.
- **Asset frontend:** CSS dan JavaScript dikembangkan melalui Vite; Tailwind CSS tersedia untuk styling.
- **Generator:** command Artisan membantu membuat halaman, layout dashboard, middleware role, dan file CRUD.

## Cara kerja frontend

Halaman dirender di server menggunakan Blade. JavaScript menambahkan interaksi tertentu, seperti navigasi ringan pada link yang dipilih. AutoLaravel bukan Vue atau React SPA; link biasa tetap bekerja dengan navigasi browser.

## Untuk aplikasi seperti apa?

AutoLaravel cocok sebagai awal untuk aplikasi Laravel server-rendered, dashboard, aplikasi CRUD, atau situs Blade yang memerlukan JavaScript ringan. Developer tetap dapat menggunakan model, controller, migration, dan fitur Laravel untuk membangun kebutuhan aplikasi.

Jika kebutuhan utama aplikasi adalah UI client-side yang kompleks atau SPA penuh, pertimbangkan frontend client-side khusus. Itu bukan pola frontend bawaan AutoLaravel.

## Urutan membaca

Ikuti bab bernomor untuk mengenal instalasi, konfigurasi, folder aplikasi, routing, backend, frontend, dan pengujian. Setelahnya, buka panduan fitur tertentu dari [indeks dokumentasi](../README.md).

---

[Indeks dokumentasi](../README.md) · Lanjut: [2. Instalasi dan persyaratan](./02-instalasi.md)

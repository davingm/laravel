# 2. Instalasi dan persyaratan

Bab ini menjelaskan perangkat yang diperlukan dan cara membuat project AutoLaravel baru.

## Persyaratan

- PHP >= 8.3.
- Composer.
- Node.js >= 20.19 atau >= 22.12.
- npm, yang tersedia bersama instalasi Node.js.

Periksa versi alat sebelum instalasi:

```bash
php --version
composer --version
node --version
npm --version
```

Pastikan ekstensi PHP dan driver database yang diperlukan oleh pilihan database aplikasi juga tersedia.

## Membuat project

Jika package AutoLaravel tersedia pada Composer registry yang Anda gunakan, buat project baru dengan Composer:

```bash
composer create-project auto/laravel nama-aplikasi
cd nama-aplikasi
```

Ganti `nama-aplikasi` dengan nama folder project yang diinginkan. Composer membuat folder project dan menjalankan proses setup yang disediakan starter. Jika package belum tersedia pada registry, minta alamat distribusi atau versi yang benar dari pengelola framework.

Ketersediaan versi `auto/laravel` pada Packagist belum diverifikasi. Jika Composer melaporkan bahwa package atau versi tidak ditemukan, pastikan registry dan versi rilis yang diberikan pengelola framework sebelum melanjutkan.

## Tentang dependency

Composer memasang dependency PHP ke `vendor/`. npm memasang dependency frontend ke `node_modules/`. Kedua folder tersebut dihasilkan oleh package manager dan bukan tempat untuk menulis kode aplikasi.

Jika proses instalasi berhenti karena ekstensi PHP atau dependency sistem tidak tersedia, pasang prasyarat yang dilaporkan lalu ulangi perintah Composer. Jangan mengedit file di dalam `vendor/` atau `node_modules/` untuk mengatasi masalah instalasi.

## Langkah berikutnya

Setelah project dibuat, ikuti [Bab 3: Memulai aplikasi](./03-memulai-aplikasi.md) untuk menyiapkan konfigurasi lokal dan menjalankan server.

---

[Kembali: 1. Pengenalan](./01-pengenalan.md) · [Indeks dokumentasi](../README.md) · Lanjut: [3. Memulai aplikasi](./03-memulai-aplikasi.md)

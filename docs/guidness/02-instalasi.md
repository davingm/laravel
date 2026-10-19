# 2. Instalasi dan persyaratan

Bab ini menjelaskan perangkat yang diperlukan dan cara membuat project `davingm/laravel` baru.

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

Buat project baru dari package yang dipublikasikan di Packagist:

```bash
composer create-project davingm/laravel nama-aplikasi
cd nama-aplikasi
```

Ganti `nama-aplikasi` dengan nama folder project yang diinginkan. Composer membuat folder project dan menjalankan setup starter. Jika Composer tidak menemukan package atau versi, periksa [halaman package di Packagist](https://packagist.org/packages/davingm/laravel) untuk memastikan status rilis terkini. Jika metadata author atau lisensi yang tampil masih lama, jalankan `composer clear-cache` dan periksa metadata pada versi atau branch yang benar-benar dipilih; mengubah `main` tidak mengubah metadata tag atau branch lain.

## Tentang dependency

Composer memasang dependency PHP ke `vendor/`. npm memasang dependency frontend ke `node_modules/`. Kedua folder tersebut dihasilkan oleh package manager dan bukan tempat untuk menulis kode aplikasi.

Jika proses instalasi berhenti karena ekstensi PHP atau dependency sistem tidak tersedia, pasang prasyarat yang dilaporkan lalu ulangi perintah Composer. Jangan mengedit file di dalam `vendor/` atau `node_modules/` untuk mengatasi masalah instalasi.

## Langkah berikutnya

Setelah project dibuat, ikuti [Bab 3: Memulai aplikasi](./03-memulai-aplikasi.md) untuk menyiapkan konfigurasi lokal dan menjalankan server.

---

[Kembali: 1. Pengenalan](./01-pengenalan.md) · [Indeks dokumentasi](../README.md) · Lanjut: [3. Memulai aplikasi](./03-memulai-aplikasi.md)

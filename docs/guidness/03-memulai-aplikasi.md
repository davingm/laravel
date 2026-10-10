# 3. Memulai aplikasi

Bab ini memandu setup dan menjalankan project AutoLaravel yang sudah dibuat melalui Composer.

Semua command dijalankan dari **root aplikasi**, yaitu folder project yang berisi `artisan`, `composer.json`, dan `package.json`.

## 1. Periksa konfigurasi database

Project baru memakai `.env` dan koneksi SQLite sebagai konfigurasi awal. Periksa `.env` setelah instalasi dan pastikan `DB_CONNECTION` serta nilai `DB_*` menunjuk ke database development yang ingin digunakan. Jangan arahkan setup ke database production.

## 2. Periksa hasil instalasi

Proses `composer create-project` menjalankan script awal starter untuk memasang dependency, membuat konfigurasi `.env`, menyiapkan application key, menjalankan migration awal, dan membangun asset frontend.

Periksa bahwa command instalasi selesai tanpa error. Migration mengubah schema database aktif; jangan jalankan ulang setup terhadap database production atau database dengan data penting.

Jika script setup terlewat atau dependency perlu dipasang ulang, jalankan dari root aplikasi:

```bash
composer run setup
```

Script setup menjalankan migration dengan `--force`. Karena itu, pastikan konfigurasi database benar sebelum menjalankannya kembali.

## 3. Jalankan mode development

Mulai development menggunakan launcher:

```bash
artisan dev
```

Jika launcher tidak terpasang atau PATH mengarah ke launcher lama:

```bash
node .laravel/cli.js dev
```

Mode ini menjalankan Laravel server, queue worker, dan Vite dev server. Biarkan terminal tetap terbuka selama development.

## 4. Jalankan perintah Artisan

Gunakan launcher `artisan` untuk menjalankan satu command:

```bash
artisan about
artisan route:list
artisan test
```

Gunakan bentuk `artisan <command>` untuk command Laravel. Launcher ini meneruskan command seperti `about`, `route:list`, `test`, `migrate`, dan generator ke aplikasi.

## 5. Buka aplikasi

Launcher menampilkan alamat server saat mulai. Buka alamat lokal tersebut di browser. Untuk pengembangan frontend, Vite dev server harus tetap aktif agar perubahan asset dapat dimuat.

## Masalah umum

- **`artisan` tidak dikenali:** gunakan `node .laravel/cli.js dev` dari root aplikasi.
- **Asset belum muncul:** pastikan Vite aktif saat development, atau jalankan `npm run build`.
- **Database error saat setup:** periksa `.env`, ketersediaan file SQLite, izin tulis, dan ekstensi PHP untuk driver database yang digunakan.
- **Route halaman belum terlihat:** periksa bahwa file berada di `src/pages/` dan jalankan `artisan route:list`.

## Langkah berikutnya

Pelajari variabel environment dan konfigurasi khusus pada [Bab 4: Konfigurasi](./04-konfigurasi.md).

---

[Kembali: 2. Instalasi dan persyaratan](./02-instalasi.md) · [Indeks dokumentasi](../README.md) · Lanjut: [4. Konfigurasi](./04-konfigurasi.md)

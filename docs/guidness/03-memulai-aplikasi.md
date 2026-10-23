# 3. Memulai aplikasi

Bab ini memandu setup dan menjalankan project `davingm/laravel` yang sudah dibuat melalui Composer.

Semua command dijalankan dari **root aplikasi**, yaitu folder project yang berisi `composer.json` dan `package.json`.

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

Pasang launcher global satu kali per mesin dan mulai development:

```bash
npm install --global @davingm/laravel-cli
artisan dev
```

Launcher mencari project dari direktori aktif, jadi command juga dapat dijalankan dari subfolder project. Mode `dev` menjalankan Laravel server, queue worker, dan Vite dev server. Biarkan terminal tetap terbuka selama development.

## 4. Jalankan perintah Artisan

Gunakan launcher framework untuk menjalankan command:

```bash
artisan about
artisan route:list
artisan test
```

Launcher global meneruskan command seperti `about`, `route:list`, `test`, `migrate`, dan generator ke runtime `.laravel/` milik aplikasi. Tidak ada file `artisan` di root project. Jika command belum ditemukan setelah instalasi, pastikan npm global bin ada di `PATH`, lalu buka terminal baru.

## 5. Buka aplikasi

Launcher menampilkan alamat server saat mulai. Buka alamat lokal tersebut di browser. Untuk pengembangan frontend, Vite dev server harus tetap aktif agar perubahan asset dapat dimuat.

## Masalah umum

- **`artisan` tidak dikenali:** periksa `npm prefix --global`, pastikan direktori bin npm global masuk ke `PATH`, lalu buka terminal baru.
- **Asset belum muncul:** pastikan Vite aktif saat development, atau jalankan `npm run build`.
- **Database error saat setup:** periksa `.env`, ketersediaan file SQLite, izin tulis, dan ekstensi PHP untuk driver database yang digunakan.
- **Route halaman belum terlihat:** periksa bahwa file berada di `src/pages/` dan jalankan `artisan route:list` atau `node .laravel/cli.js route:list`.

## Langkah berikutnya

Pelajari variabel environment dan konfigurasi khusus pada [Bab 4: Konfigurasi](./04-konfigurasi.md).

---

[Kembali: 2. Instalasi dan persyaratan](./02-instalasi.md) · [Indeks dokumentasi](../README.md) · Lanjut: [4. Konfigurasi](./04-konfigurasi.md)

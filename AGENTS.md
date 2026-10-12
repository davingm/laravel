# AGENTS.md — davingm/laravel

Baca file ini dulu sebelum mengubah apa pun. Isinya konteks proyek dan keputusan yang sudah diambil, supaya tidak perlu dijelaskan ulang.

## Aturan umum

- Informasi teknis (versi, sintaks, serta perilaku GitHub Actions / Composer / Packagist / Laravel) harus diverifikasi dari sumber resmi atau pencarian, bukan dari ingatan. Jika tidak bisa diverifikasi, tulis "belum diverifikasi".
- Jangan mengubah struktur proyek tanpa alasan. Periksa kondisi aktual repo sebelum bertindak.
- Perintah destruktif (`migrate:fresh`, `rm -rf`, dsb.) jangan dijalankan pada database atau folder default. Pakai SQLite sementara atau salinan terisolasi.
- Balas dalam bahasa Indonesia.

## Tujuan proyek

Membuat ekosistem Laravel milik sendiri dengan gaya repository Nuxt: satu repository pengembangan berisi source framework di `packages/laravel` dan satu aplikasi playground. Folder runtime `.laravel` dihasilkan oleh Composer dari source tersebut dan diabaikan Git.

Pola: **Nuxt-style monorepo + Composer-generated runtime directory.**

## Struktur target

```
laravel-davingm/
├── packages/
│   ├── laravel/      → source untuk runtime .laravel
│   ├── auth/         → package auth
│   ├── dev/          → placeholder
│   └── <package>/    → package tambahan
├── playground/       → aplikasi Laravel utama (kerangka kerja pengguna), tempat menguji package
├── docs/
├── scripts/
├── .github/workflows/split-packages.yml
├── .gitattributes
├── .gitignore
└── README.md
```

- `playground/` adalah aplikasi pengembangan dan pengujian.
- Source framework `.laravel/` berada di `packages/laravel/.laravel/`. Composer menyalinnya ke `playground/.laravel/` atau root aplikasi hasil instalasi setelah autoload dibuat.
- `.laravel/` hasil generate diabaikan Git; source yang diedit harus berada di `packages/laravel/.laravel/`.
- Jangan memakai nama `packages/laravel` untuk starter utama. Starter ada di `playground/`.
- Folder `public/` di root sudah dihapus dan tidak dipakai. Yang aktif adalah `playground/public`.

## Riwayat singkat

1. `packages/auth` dibuat: installer `auth:install`, route hanya dimuat jika `davingm-auth.enabled`, migrasi username, halaman login dan dashboard.
2. Diuji lokal lewat Composer path repository (instalasi, migrasi SQLite sementara, alur login/logout), lalu dibersihkan kembali.
3. Kerangka dikembalikan bersih tanpa integrasi auth, package auth tidak lagi menjadi path dependency.
4. Workflow split dibuat dan diaudit (lihat bawah).
5. Semua file Laravel dipindahkan manual ke `playground/`. `.gitignore` dan `.gitattributes` disesuaikan. Di `playground/`: `artisan about` dan `route:list` jalan, `php artisan test` lulus 8 test / 21 assertion, `npm run build` berhasil.

## CI (`.github/workflows/test-apache.yml`)

- Berjalan pada setiap push dan pull request.
- Memasang dependency Composer dari `playground/`, yang membangkitkan `.laravel/` dari `../packages/laravel/.laravel/`.
- Menjalankan test Laravel, build frontend, lalu HTTP smoke test lewat Apache.
- Workflow split package sudah dihapus dan dokumen lamanya diarsipkan.

## Perintah yang dipakai

Jalankan dari `playground/`:

```bash
php artisan about
php artisan route:list
php artisan test --compact
npm run build
```

Catatan lingkungan (Windows, Git Bash / PowerShell):

- Jika `rm -rf public` gagal "Device or resource busy", biasanya ada proses PHP server uji yang tertinggal. Hentikan proses `php` dulu.
- Beberapa test gagal karena izin tulis pada `bootstrap/cache` dan `.laravel/cache` di sandbox. Ini masalah izin, bukan bug Laravel.
- Build frontend di sandbox pernah gagal memuat binding native Tailwind. Dengan izin yang sesuai build berhasil.

## Distribusi starter

Root Composer mematerialisasi isi `playground/`. Source `packages/laravel/.laravel/` disertakan dalam archive starter agar `composer install` dapat meregenerasi folder runtime pada project baru maupun clone berikutnya. Package internal `auth` dan `dev` tetap dikecualikan dari archive.

## Yang perlu dicek berikutnya

- Memastikan archive `composer create-project` menyertakan `packages/laravel/.laravel/` dan mengecualikan package internal lain.
- Mengisi `packages/dev` dan menambah package lain dengan konvensi `davingm/<nama-folder>`.
- Sinkronkan `composer.lock` jika ada perubahan manifest (sebelumnya gagal karena koneksi Packagist ditolak).

# AGENTS.md — davingm/laravel

Baca file ini dulu sebelum mengubah apa pun. Isinya konteks proyek dan keputusan yang sudah diambil, supaya tidak perlu dijelaskan ulang.

## Aturan umum

- Informasi teknis (versi, sintaks, perilaku GitHub Actions / Composer / Packagist / Laravel) harus diverifikasi dari sumber resmi atau pencarian, bukan dari ingatan. Jika tidak bisa diverifikasi, tulis "belum diverifikasi".
- Jangan mengubah struktur proyek tanpa alasan. Periksa kondisi aktual repo sebelum bertindak.
- Perintah destruktif (`migrate:fresh`, `rm -rf`, dsb.) jangan dijalankan pada database atau folder default. Pakai SQLite sementara atau salinan terisolasi.
- Balas dalam bahasa Indonesia.

## Tujuan proyek

Membuat ekosistem Laravel milik sendiri dengan gaya repository Nuxt: satu repository pusat pengembangan (`davingm/laravel`) berisi banyak package Composer dan satu aplikasi playground. Setiap package dipublikasikan otomatis ke repository GitHub terpisah, lalu didistribusikan lewat Packagist.

Pola: **Nuxt-style monorepo + Composer package split + Packagist distribution.**

## Struktur target

```
laravel-davingm/
├── packages/
│   ├── auth/         → dipublikasikan sebagai davingm/auth
│   ├── dev/          → davingm/dev (saat ini masih kosong)
│   └── <package>/    → konvensi: davingm/<nama-folder>
├── playground/       → aplikasi Laravel utama (kerangka kerja pengguna), tempat menguji package
├── docs/
├── scripts/
├── .github/workflows/split-packages.yml
├── .gitattributes
├── .gitignore
└── README.md
```

- `playground/` adalah lingkungan pengembangan dan pengujian. Bukan package yang di-split.
- Jangan memakai nama `packages/laravel` untuk starter utama. Starter ada di `playground/`.
- Folder `public/` di root sudah dihapus dan tidak dipakai. Yang aktif adalah `playground/public`.

## Riwayat singkat

1. `packages/auth` dibuat: installer `auth:install`, route hanya dimuat jika `davingm-auth.enabled`, migrasi username, halaman login dan dashboard.
2. Diuji lokal lewat Composer path repository (instalasi, migrasi SQLite sementara, alur login/logout), lalu dibersihkan kembali.
3. Kerangka dikembalikan bersih tanpa integrasi auth, package auth tidak lagi menjadi path dependency.
4. Workflow split dibuat dan diaudit (lihat bawah).
5. Semua file Laravel dipindahkan manual ke `playground/`. `.gitignore` dan `.gitattributes` disesuaikan. Di `playground/`: `artisan about` dan `route:list` jalan, `php artisan test` lulus 8 test / 21 assertion, `npm run build` berhasil.

## Workflow split (`.github/workflows/split-packages.yml`)

- Pemicu: tag `<package>-vX.Y.Z` (contoh `auth-v1.0.0`).
- Memakai `git subtree split` pada `packages/<package>`, memeriksa nama Composer `davingm/<package>`, lalu push branch `main` dan tag bersih `vX.Y.Z` ke `davingm/<package>`.
- Secret: `MONOREPO_SPLIT_TOKEN` (jangan hardcode kredensial).
- Push atomic, tanpa force. Aman diulang untuk tag dan commit yang sama.
- Repository tujuan **harus dibuat manual** dan kosong (branch `main`). Workflow tidak membuatnya.
- Workflow menolak split ke repo yang sama dengan sumber.
- Webhook Packagist tidak diatur workflow. Harus dikonfigurasi terpisah pada repo tujuan.
- Risiko: repo tujuan yang sudah punya commit atau tag berbeda akan menolak push (tidak ditimpa).
- Panduan setup ada di `docs/package-split-releases.md`.
- Workflow belum pernah dijalankan sungguhan.

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
- Beberapa test gagal karena izin tulis pada `bootstrap/cache` dan `.davingm/cache` di sandbox. Ini masalah izin, bukan bug Laravel.
- Build frontend di sandbox pernah gagal memuat binding native Tailwind. Dengan izin yang sesuai build berhasil.

## Keputusan yang belum final

**Distribusi `composer create-project davingm/laravel app`.** `composer.json` aplikasi kini ada di `playground/`, bukan di root. Composer membaca root repo, jadi `.gitattributes` bisa mengecualikan file tetapi tidak memindahkan isi `playground/` ke root hasil instalasi. Pilihan yang perlu diputuskan pemilik proyek:

1. Hasil instalasi berada di `app/playground/`, atau
2. Hasil instalasi langsung di `app/` (butuh mekanisme lain, misalnya repo distribusi terpisah yang di-split dari `playground/`).

Jangan memilih sendiri. Tanyakan ke pemilik proyek sebelum mengubah `composer.json`, `.gitattributes`, atau workflow terkait distribusi.

## Yang perlu dicek berikutnya

- Apakah `.gitattributes` (`export-ignore`) sudah sesuai dengan opsi distribusi yang dipilih.
- Menentukan cara menguji workflow split dengan aman (repo tujuan uji, token dengan akses terbatas).
- Mengisi `packages/dev` dan menambah package lain dengan konvensi `davingm/<nama-folder>`.
- Sinkronkan `composer.lock` jika ada perubahan manifest (sebelumnya gagal karena koneksi Packagist ditolak).
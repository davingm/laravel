# AGENTS.md — davingm/laravel

Baca file ini dulu sebelum mengubah apa pun. Isinya konteks proyek dan keputusan yang sudah diambil, supaya tidak perlu dijelaskan ulang.

## Aturan umum

- Sebelum merencanakan atau mengubah proyek, selalu tinjau `docs/README.md` dan baca dokumentasi di `docs/` yang relevan dengan tugas. Jangan menganggap diskusi historis sebagai spesifikasi implementasi terkini.
- Pahami pembagian dokumentasi: `docs/dev/` berisi panduan developer yang mengembangkan framework; `docs/guidness/` berisi panduan pengguna framework dan fitur yang tersedia; `docs/discussion/` berisi diskusi teknis, rancangan, dan catatan arah/tujuan pengembangan. Periksa status dan kesesuaian dokumen discussion terhadap source sebelum menjadikannya dasar perubahan.
- Informasi teknis (versi, sintaks, serta perilaku GitHub Actions / Composer / Packagist / Laravel) harus diverifikasi dari sumber resmi atau pencarian, bukan dari ingatan. Jika tidak bisa diverifikasi, tulis "belum diverifikasi".
- Jangan mengubah struktur proyek tanpa alasan. Periksa kondisi aktual repo sebelum bertindak.
- Perintah destruktif (`migrate:fresh`, `rm -rf`, dsb.) jangan dijalankan pada database atau folder default. Pakai SQLite sementara atau salinan terisolasi.
- Balas dalam bahasa Indonesia.

## Tujuan proyek

Membuat ekosistem Laravel milik sendiri dengan gaya repository Nuxt: satu repository pengembangan berisi source framework di `packages/laravel` dan satu aplikasi playground. Folder runtime `.laravel` dihasilkan oleh Composer dari source tersebut dan diabaikan Git.

## Dokumentasi proyek

Sebelum mengerjakan tugas, baca `docs/README.md` untuk memahami indeks dan status dokumentasi. Baca dokumen terkait sebelum mengubah implementasi atau dokumentasi, lalu sinkronkan dokumentasi yang terdampak.

- `docs/dev/`: panduan khusus developer yang membangun, menguji, dan merilis framework atau package-nya. Contoh: mengembangkan source `packages/laravel` melalui `playground/` dan menerbitkan runtime package terpisah.
- `docs/guidness/`: dokumentasi framework untuk pengguna, mencakup cara memakai framework dan fitur-fiturnya. Jaga instruksi dan contoh agar sesuai dengan implementasi saat ini.
- `docs/discussion/`: ruang diskusi teknis mengenai rancangan, alternatif arsitektur, serta arah dan tujuan pengembangan framework. Dokumen di sini dapat berupa catatan historis atau proposal; verifikasi status dan implementasinya sebelum memperlakukannya sebagai keputusan aktif.

# Direktory Referensi

Proyek ini tidak di bangun dari ide murni semua ide terbentuknya framowks besar ini adalah gabungan dari bebrapa referensi dari proyek open source lain. jadi biasakan selalu buka </references/**> untuk melihat draft folder referensi. pastikan folder ini selalu di ignore baik untuk git dan juga packages, Notes jangan ubah apa apa di folder itu

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
├── .github/workflows/test-apache.yml
├── .gitattributes
├── .gitignore
└── README.md
```

- `playground/` adalah aplikasi pengembangan dan pengujian.
- Runtime source `.laravel/` dan Composer plugin berada di `packages/laravel/`. Plugin membuat folder runtime di root aplikasi setelah autoload dibuat.
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
- Memasang dependency Composer dari `playground/`; plugin `davingm/laravel-runtime` membangkitkan `.laravel/`.
- Menjalankan test Laravel, build frontend, lalu HTTP smoke test lewat Apache.
- Workflow split package sudah dihapus. Runtime plugin perlu dirilis terpisah agar project aplikasi tidak membawa source internal.

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

Root Composer mematerialisasi isi `playground/`. Package runtime disediakan sebagai dependency Composer dan folder `packages/laravel` dikecualikan dari archive starter. Package `davingm/laravel-runtime` harus tersedia di Packagist agar `create-project` dan clone aplikasi bisa memasang plugin tanpa membawa source internal ke repo aplikasi.

## Yang perlu dicek berikutnya

- Merilis package plugin `davingm/laravel-runtime` agar create-project dan clone aplikasi dapat memasangnya dari Packagist.
- Mengisi `packages/dev` dan menambah package lain dengan konvensi `davingm/<nama-folder>`.
- Sinkronkan `composer.lock` jika ada perubahan manifest (sebelumnya gagal karena koneksi Packagist ditolak).

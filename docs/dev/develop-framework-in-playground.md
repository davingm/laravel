# Mengembangkan framework melalui playground

Panduan ini untuk pengembang `davingm/laravel`. Tujuannya membedakan source framework, aplikasi playground, dan runtime hasil instalasi Composer, serta menjelaskan cara menguji perubahan sebelum menerbitkan package runtime.

## Peta folder

| Folder | Kegunaan | Jika ingin mengubah... |
| --- | --- | --- |
| `packages/laravel/src/` | Composer plugin yang memasang runtime | Cara package dipasang atau lifecycle plugin |
| `packages/laravel/.laravel/` | Source runtime framework | CLI, generator, template, dan file runtime |
| `playground/` | Aplikasi Laravel untuk mencoba framework | Aplikasi contoh, routes, middleware, dan integrasi package |
| `playground/.laravel/` | Hasil generate dari plugin Composer; diabaikan Git | Jangan edit langsung. Ubah source di `packages/laravel/.laravel/` |

Plugin menyalin runtime dari package yang terpasang ke `.laravel/` pada root aplikasi. Karena itu, `playground/.laravel/` adalah hasil build lokal, bukan source utama.

## Menjalankan playground dengan versi package yang sudah dirilis

Jalankan dari folder `playground/`:

```bash
composer install
php artisan about
php artisan route:list
php artisan test --compact
npm ci
npm run build
```

Untuk menjalankan server pengembangan gunakan `composer run dev` dari folder tersebut. Perintah ini menjalankan CLI framework yang sudah terpasang oleh Composer.

Pastikan runtime sudah terbentuk:

```bash
test -f .laravel/cli.js
```

Pada PowerShell, pemeriksaan terakhir dapat memakai `Test-Path .laravel/cli.js`.

## Menguji perubahan source lokal `packages/laravel`

Composer path repository membuat dependency menunjuk ke folder package lokal. Composer akan memakai symlink jika sistem mendukungnya, atau menyalin package sebagai fallback. Konfigurasi ini ditambahkan sementara ke `playground/composer.json`; jangan commit override lokal ini ke starter aplikasi.

1. Simpan perubahan kerja yang sedang ada pada `playground/composer.json` dan `playground/composer.lock`, agar bisa dikembalikan tanpa membuang pekerjaan lain.
2. Edit `playground/composer.json`:
   - Tambahkan repository berikut di tingkat root, sejajar dengan `require`:

     ```json
     "repositories": [
         {
             "type": "path",
             "url": "../packages/laravel",
             "options": {
                 "symlink": true
             }
         }
     ],
     ```

   - Ubah sementara constraint `davingm/laravel-runtime` dalam `require` dari `^1.0` menjadi `dev-main`.

   `dev-main` adalah versi branch `main` yang dikenali Composer. Jika branch monorepo yang sedang dipakai bukan `main`, gunakan constraint `dev-<nama-branch>` yang sesuai.

3. Dari folder `playground/`, perbarui package dan jalankan ulang lifecycle Composer:

   ```bash
   composer update davingm/laravel-runtime -W
   composer show davingm/laravel-runtime
   composer dump-autoload
   ```

   Output Composer seharusnya menunjukkan package bersumber dari `../packages/laravel`. Periksa juga bahwa file runtime yang berubah tersedia di `.laravel/`.

4. Uji perubahan melalui aplikasi playground:

   ```bash
   php artisan about
   php artisan route:list
   php artisan test --compact
   composer run dev
   ```

   Untuk perubahan asset frontend, jalankan `npm ci` jika dependency belum terpasang, lalu `npm run build`.

5. Setelah selesai, kembalikan perubahan sementara pada `playground/composer.json` dan `playground/composer.lock` ke konfigurasi rilis (`^1.0` dan tanpa path repository), lalu jalankan:

   ```bash
   composer update davingm/laravel-runtime -W
   ```

   Periksa `git diff` sebelum commit. Jangan memakai `git restore` secara membabi buta jika file tersebut juga memiliki perubahan lain.

> Catatan: Composer memuat konfigurasi repository dari root project. Path repository dan opsi symlink di atas mengikuti dokumentasi Composer untuk monorepo. Jika package path lokal terdeteksi dari branch lain, sesuaikan versi branch sebagaimana aturan versi branch Composer. [Path repository Composer](https://getcomposer.org/doc/05-repositories.md#path), [versi dan constraint Composer](https://getcomposer.org/doc/articles/versions.md).

## Menerbitkan `packages/laravel` ke repository terpisah

Package Composer bernama `davingm/laravel-runtime`; repository target-nya [github.com/davingm/laravel-runtime](https://github.com/davingm/laravel-runtime). Kerjakan perintah Git berikut dari **root monorepo** di Git Bash. Git PowerShell pada lingkungan pengembang proyek ini tidak tersedia.

### 1. Siapkan perubahan monorepo

Commit perubahan source terlebih dahulu. Bila perubahan juga menyentuh playground, commit perubahan itu sesuai kebiasaan proyek, lalu push branch utama monorepo:

```bash
git status --short
git add packages/laravel
git commit -m "Update Laravel runtime"
git push origin main
```

Jika commit juga mengubah file di luar `packages/laravel`, stage file-file tersebut secara eksplisit. `git subtree split` hanya membentuk riwayat dari folder package pada commit yang diberikan.

### 2. Tambahkan remote package satu kali

Lewati langkah ini jika remote `runtime` sudah ada:

```bash
git remote add runtime https://github.com/davingm/laravel-runtime.git
git remote -v
```

Repository tujuan harus sudah dibuat dan branch/tag yang akan dipush harus belum bertabrakan dengan riwayat berbeda.

### 3. Split source dan push branch

Pilih versi baru yang belum pernah dipakai. Karena `v1.0.1` sudah dirilis, `v1.0.2` hanya contoh untuk rilis patch berikutnya.

```bash
split_commit=$(git subtree split --prefix=packages/laravel) || exit 1
test -n "$split_commit" || { echo "git subtree split gagal" >&2; exit 1; }

git show --stat --oneline "$split_commit"
git push runtime "$split_commit:refs/heads/main"
```

Hasil split menempatkan isi `packages/laravel` di root history package. Commit ID hasil split boleh ditinjau sebelum push. Perintah `git subtree split` menghasilkan commit synthetic untuk subtree; lihat [dokumentasi Git subtree](https://github.com/git/git/blob/master/contrib/subtree/git-subtree.adoc).

### 4. Push tag versi package

Push tag ke repository package dengan menunjuk langsung ke commit hasil split:

```bash
git push runtime "$split_commit:refs/tags/v1.0.2"
git ls-remote runtime refs/heads/main refs/tags/v1.0.2
```

Ganti `v1.0.2` dengan versi yang dipilih. Tag versi diterbitkan pada repo `laravel-runtime`, bukan pada riwayat aplikasi monorepo. Jangan memindahkan atau menimpa tag yang sudah diterbitkan, dan jangan gunakan force push untuk mengatasi penolakan remote.

### 5. Verifikasi package rilis

1. Pastikan GitHub repo `davingm/laravel-runtime` menampilkan source package di root, termasuk `composer.json`, `src/`, dan `.laravel/`.
2. Pastikan Packagist menampilkan tag versi baru. Jika webhook belum aktif, sinkronkan package dari halaman Packagist.
3. Uji konsumsi package dari checkout/salinan playground yang **tidak** memiliki repository path lokal. Dari folder aplikasi:

   ```bash
   composer clear-cache
   composer update davingm/laravel-runtime --with-dependencies
   composer show davingm/laravel-runtime
   composer install
   test -f .laravel/cli.js
   php artisan test --compact
   ```

Dokumentasi lengkap untuk validasi metadata, Packagist, dan troubleshooting ada di [publish-runtime-package.md](publish-runtime-package.md).

## Catatan kerja

- Perubahan framework ada di `packages/laravel`; perubahan aplikasi playground ada di `playground/`.
- Jangan edit `playground/.laravel/` karena folder itu dihasilkan ulang oleh Composer.
- Pastikan semua perubahan package sudah di-commit di monorepo sebelum split dan push.
- Uji hasil rilis dari instalasi Composer biasa tanpa path repository lokal, supaya yang diuji benar-benar artifact package terbit.

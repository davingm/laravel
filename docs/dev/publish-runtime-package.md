# Merilis package runtime

Panduan ini menjelaskan cara merilis source `packages/laravel` sebagai package Composer `davingm/laravel-runtime`. Source dikembangkan di monorepo ini; repository rilis terpisah berisi isi `packages/laravel` di root-nya.

> Jangan menganggap versi atau status sinkronisasi dalam catatan lama sebagai kondisi saat ini. Sebelum merilis, periksa branch dan tag repository package serta metadata [Packagist](https://packagist.org/packages/davingm/laravel-runtime). Jangan menimpa atau memindahkan tag yang sudah diterbitkan.

## Sebelum merilis

Pastikan:

- Perubahan source yang hendak dirilis sudah ditinjau dan diuji melalui `playground/`.
- `packages/laravel/composer.json` memiliki nama package `davingm/laravel-runtime` dan class plugin yang benar.
- Repository tujuan [github.com/davingm/laravel-runtime](https://github.com/davingm/laravel-runtime) dapat diakses dan remote `runtime` mengarah ke sana.
- `git subtree` tersedia; perintah berikut dijalankan dari root monorepo.
- Versi/tag yang dipilih belum pernah diterbitkan di repository tujuan.

## Validasi source

Dari root monorepo:

```bash
composer validate --strict --no-check-publish --working-dir=packages/laravel
php -l packages/laravel/src/RuntimePlugin.php
```

Lalu dari `playground/`, jalankan test dan build yang relevan:

```bash
node .laravel/cli.js test --compact
npm run build
```

## Siapkan remote dan versi

Gunakan Git Bash, WSL, Linux, atau macOS untuk contoh Bash di bawah. Di Windows, pastikan `git subtree` tersedia pada lingkungan shell tersebut.

Periksa remote dan tag package sebelum memilih versi:

```bash
git remote -v
git ls-remote --heads --tags https://github.com/davingm/laravel-runtime.git
```

Jika remote `runtime` belum ada, tambahkan sekali:

```bash
git remote add runtime https://github.com/davingm/laravel-runtime.git
```

Pilih versi SemVer baru yang belum ada. Konfirmasikan juga versi dan metadata di halaman Packagist. Jangan membuat atau memindahkan tag hanya untuk memperbarui metadata rilis lama.

## Split source dan push

Pastikan perubahan yang diinginkan sudah ada pada commit monorepo yang benar. Perintah `git subtree split` hanya membawa riwayat dari `packages/laravel`.

```bash
git status --short
split_commit=$(git subtree split --prefix=packages/laravel) || exit 1
test -n "$split_commit" || { echo "git subtree split gagal" >&2; exit 1; }

git show --stat --oneline "$split_commit"
```

Tinjau commit split sebelum push. Ganti `vX.Y.Z` pada perintah berikut dengan tag baru yang sudah dipastikan belum digunakan:

```bash
git push runtime "$split_commit:refs/heads/main"
git push runtime "$split_commit:refs/tags/vX.Y.Z"
git ls-remote runtime refs/heads/main "refs/tags/vX.Y.Z"
```

Jika push ditolak karena remote memiliki commit yang tidak ada pada riwayat split, hentikan dan tinjau riwayatnya. Jangan gunakan force push sebagai jalan pintas. Tag versi dipasang pada repository package runtime, bukan pada monorepo.

## Sinkronkan dan verifikasi Packagist

Packagist membaca manifest package dari root repository VCS. Setelah push:

1. Pastikan repository package memiliki `composer.json`, `src/`, dan `.laravel/` pada root.
2. Pastikan branch/tag baru terlihat pada GitHub.
3. Tunggu webhook Packagist atau minta pembaruan manual dari halaman package bila webhook tidak tersedia.
4. Periksa versi dan metadata pada [halaman package runtime](https://packagist.org/packages/davingm/laravel-runtime).

Untuk menguji konsumsi rilis, gunakan checkout baru atau direktori sementara yang tidak memiliki path repository lokal ke `../packages/laravel`. Jalankan dari root aplikasi uji:

```bash
composer clear-cache
composer update davingm/laravel-runtime --with-dependencies --no-interaction
composer show davingm/laravel-runtime
composer install --no-interaction --prefer-dist
test -f .laravel/cli.js
test -f .laravel/artisan.php
node .laravel/cli.js test --compact
```

Jangan arahkan uji instalasi ke project atau database yang menyimpan data penting. Untuk menguji starter baru dari Packagist, jalankan `composer create-project davingm/laravel /path/to/temporary-directory --no-interaction` dengan path pengganti yang belum ada dan berada di lingkungan uji.

## Pemecahan masalah

- **Versi baru belum muncul:** pastikan tag ada di repository tujuan, lalu cek webhook atau minta Packagist memperbarui package secara manual.
- **Composer mengambil versi lama:** periksa constraint dan `composer.lock`, cache Composer, serta versi yang benar-benar tercatat di Packagist.
- **Composer meminta izin plugin:** pastikan nama dan sumber package benar serta `davingm/laravel-runtime` diizinkan melalui `config.allow-plugins`.
- **Folder `.laravel/` tidak dibuat:** pastikan plugin berhasil dipasang dan diizinkan, lalu jalankan `composer dump-autoload` untuk menjalankan lifecycle Composer.
- **`git push` ditolak:** hentikan proses dan tinjau remote serta tag yang sudah ada. Jangan force-push atau menimpa tag.

Informasi tentang branch, tag, dan package dapat berubah. Verifikasi dengan remote VCS dan Packagist setiap kali menyiapkan rilis.

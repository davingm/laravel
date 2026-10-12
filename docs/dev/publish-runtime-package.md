# Rilis package runtime

Panduan ini menerbitkan source `packages/laravel` sebagai package Composer terpisah bernama `davingm/laravel-runtime`. Source tetap dikembangkan di monorepo `davingm/laravel`; repository rilis berisi hasil split folder tersebut saja.

## Gambaran alur

1. Validasi package dan uji melalui playground.
2. Buat repository GitHub kosong bernama `laravel-runtime` di akun/organisasi `davingm`.
3. Bentuk riwayat Git yang hanya berisi `packages/laravel`, lalu push branch `main` dan tag versi.
4. Daftarkan repository rilis di Packagist dan aktifkan sinkronisasi.
5. Verifikasi package melalui checkout aplikasi yang tidak memakai path repository lokal.

Composer mendapatkan versi dari branch dan tag VCS. Packagist membaca `composer.json` di root repository, jadi isi `packages/laravel` harus berada di root repository hasil split. Lihat [versi Composer](https://getcomposer.org/doc/articles/versions.md), [publikasi package](https://getcomposer.org/doc/02-libraries.md#publishing-to-a-version-control-system), dan [Packagist](https://packagist.org/about).

## Prasyarat

- Perubahan yang akan dirilis sudah masuk ke branch monorepo yang benar.
- `packages/laravel/composer.json` bernama `davingm/laravel-runtime` dan mendefinisikan class plugin yang benar.
- Git dengan perintah `git subtree` tersedia.
- Anda punya hak push ke repository GitHub tujuan.
- Repository `davingm/laravel-runtime` sudah dibuat **kosong**. Jangan inisialisasi README/license di GitHub karena riwayat itu tidak berhubungan dengan hasil split.

## Validasi package

Jalankan dari root monorepo:

```powershell
composer validate --strict --no-check-publish --working-dir=packages/laravel
php -l packages/laravel/src/RuntimePlugin.php
```

Uji perubahan runtime melalui playground sebelum rilis.

## Split dan push versi pertama

Jalankan dari root monorepo di Git Bash. Sesuaikan URL bila repository berada di organisasi/akun lain. `v1.0.0` adalah contoh; gunakan hanya jika tag tersebut belum pernah diterbitkan.

```bash
$splitCommit = git subtree split --prefix=packages/laravel
test -n "$splitCommit" || { echo "git subtree split gagal" >&2; exit 1; }

git push https://github.com/davingm/laravel-runtime.git "$splitCommit:refs/heads/main" || exit 1

git push https://github.com/davingm/laravel-runtime.git "$splitCommit:refs/tags/v1.0.0" || exit 1
```

Tinjau commit dan tag sebelum push. Jangan force push atau memindahkan tag versi yang sudah diterbitkan. Jika `git subtree` belum tersedia, aktifkan/pasang Git Subtree sesuai sistem operasi; langkah instalasi spesifik OS belum dicakup di sini.

## Daftarkan di Packagist

1. Masuk ke [Packagist](https://packagist.org/) dengan akun yang berhak mengelola package.
2. Pilih **Submit** dan masukkan `https://github.com/davingm/laravel-runtime`.
3. Submit package yang terdeteksi sebagai `davingm/laravel-runtime`.
4. Pastikan halaman package menampilkan versi `1.0.0`.
5. Aktifkan sinkronisasi GitHub dan Packagist. Packagist dapat memasang service hook melalui akses akun; jika tidak, konfigurasikan webhook pada repository tujuan sesuai instruksi Packagist.

Package ini adalah Composer plugin. Project starter sudah mengizinkan `davingm/laravel-runtime` melalui `config.allow-plugins`. Untuk instalasi awal, Composer dapat meminta persetujuan plugin; pastikan nama dan sumbernya benar sebelum menyetujui. Lihat [dokumentasi plugin Composer](https://getcomposer.org/doc/articles/plugins.md).

## Verifikasi konsumsi package

Gunakan checkout atau salinan sementara aplikasi. Pastikan salinan itu **tidak** memiliki konfigurasi path repository lokal `../packages/laravel`.

```powershell
composer clear-cache
composer update davingm/laravel-runtime --with-dependencies --no-interaction
composer show davingm/laravel-runtime
composer install --no-interaction --prefer-dist
Test-Path .laravel/cli.js
Test-Path .laravel/generators/MakeRolesCommand.php
php artisan test --compact
```

Dua perintah `Test-Path` seharusnya menghasilkan `True`. Jika Composer masih mengambil package lokal/versi lama, periksa `composer.json`, `composer.lock`, cache Composer, dan status sinkronisasi Packagist.

Sesudah verifikasi, sinkronkan `playground/composer.lock` dengan package yang sudah dirilis dan uji `composer create-project davingm/laravel` dari direktori sementara.

## Rilis berikutnya

1. Ubah source di `packages/laravel`, lalu validasi dan uji melalui playground.
2. Pilih tag semver baru, misalnya `v1.0.1` untuk perbaikan kompatibel atau `v1.1.0` untuk penambahan kompatibel.
3. Split ulang dari commit monorepo terbaru yang memuat perubahan, lalu push branch dan tag baru:

```bash
$splitCommit = git subtree split --prefix=packages/laravel
test -n "$splitCommit" || { echo "git subtree split gagal" >&2; exit 1; }
git push https://github.com/davingm/laravel-runtime.git "$splitCommit:refs/heads/main" || exit 1
git push https://github.com/davingm/laravel-runtime.git "$splitCommit:refs/tags/v1.0.1" || exit 1
```

4. Pastikan Packagist menampilkan versi baru. Jika webhook aktif, Packagist akan mendapat notifikasi push; jika tidak, jalankan pembaruan manual dari halaman package.
5. Perbarui constraint/lock file aplikasi bila diperlukan, lalu uji instalasi kembali.

Jangan menimpa tag lama. Pastikan perubahan kompatibilitas sesuai semantic versioning dan constraint package aplikasi.

## Pemecahan masalah

- **Packagist menolak repository/package tidak ditemukan:** pastikan `composer.json` ada di root hasil split, nama package tepat, dan repo dapat dibaca Packagist.
- **Versi belum muncul:** pastikan tag ada di repository tujuan, lalu cek sinkronisasi atau minta Packagist memperbarui package.
- **Composer meminta izin plugin:** periksa package `davingm/laravel-runtime` dan izinkan melalui `allow-plugins` project.
- **Push ditolak:** remote mungkin tidak kosong atau branch/tag sudah ada. Periksa riwayat remote; jangan gunakan force push.
- **`.laravel` tidak terbentuk:** pastikan plugin terpasang dan diizinkan, lalu jalankan `composer dump-autoload` untuk memicu event Composer.


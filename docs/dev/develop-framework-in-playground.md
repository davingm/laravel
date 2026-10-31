# Mengembangkan framework melalui playground

Panduan ini menjelaskan cara mengubah source framework, mengujinya di aplikasi playground, dan menerbitkan runtime package yang source-nya berada di `packages/laravel`.

## Peta folder

| Folder | Kegunaan |
| --- | --- |
| `packages/laravel/src/` | Source PHP package framework dan Composer plugin; di-autoload dari package/vendor dan tidak disalin ke runtime aplikasi. |
| `packages/laravel/.laravel/` | Source CLI, bootstrap Artisan internal, TypeScript framework browser, generator, dan tooling runtime. |
| `playground/` | Aplikasi Laravel untuk pengembangan dan pengujian framework. |
| `playground/.laravel/` | Runtime yang disalin Composer; output lokal dan diabaikan Git. Jangan mengeditnya langsung. |
| `playground/src/index.blade.php` | Entry frontend yang memilih layout dan menempatkan halaman melalui PageRouter. |
| `playground/src/layouts/default.blade.php` | Layout default aplikasi playground. |
| `playground/src/assets/ts/app.ts` | Entry TypeScript bersama untuk interaksi aplikasi playground, bukan implementasi runtime framework. |

`playground/tests/` dan tooling pengembangan tetap berada di repository untuk memeriksa framework. Materialisasi dan bundler starter mengecualikan folder `tests/` serta `scripts/` agar kode pengembangan tidak masuk ke project pengguna.

Plugin Composer menyalin isi `packages/laravel/.laravel/` ke `.laravel/` pada root aplikasi. Root aplikasi hasil `create-project` tidak memiliki file `artisan`; command Laravel dijalankan melalui CLI framework. Entry TypeScript framework browser juga disediakan package dan dimuat oleh Vite dari `.laravel/`. Source PHP package tetap berada di `packages/laravel/src/` dalam package/vendor dan tidak ikut disalin.

Untuk peta isi package dan batas antara source Composer `packages/laravel/src/` dengan runtime yang disalin ke aplikasi, lihat [Struktur `packages/laravel`](./packages-laravel.md).

## Setup dan pemeriksaan playground

Jalankan dari `playground/`:

```bash
composer install
node .laravel/cli.js about
node .laravel/cli.js route:list
node .laravel/cli.js test --compact
npm ci
npm run build
```

Untuk menjalankan server pengembangan, gunakan `composer run dev` atau `artisan dev`. `composer create-project` memasang launcher npm global bila belum terpasang; kegagalan instalasi ditampilkan sebagai peringatan beserta perintah manual. Launcher meneruskan command ke runtime project dan tidak membutuhkan file `artisan` di root. Untuk diagnosis, jalankan `node .laravel/cli.js dev` dari root project.

## Menguji source lokal `packages/laravel`

`playground/composer.json` menggunakan Composer path repository ke `../packages/laravel`, sehingga perubahan package lokal dapat langsung diuji tanpa menunggu rilis Packagist. Untuk menyegarkan package dan membangkitkan ulang runtime setelah perubahan:

```bash
composer update davingm/laravel-runtime
composer dump-autoload
test -f .laravel/artisan.php
node .laravel/cli.js about
node .laravel/cli.js route:list
node .laravel/cli.js test --compact
```

Jalankan perubahan aplikasi dari `playground/`. Jalankan `npm ci` jika dependency frontend belum ada dan `npm run build` jika perubahan menyentuh asset.

Path repository lokal hanya untuk monorepo. `.materialize-playground.php` menghapus konfigurasi repository lokal dan mengganti constraint `dev-main` menjadi `^1.0.5` saat membuat starter. Constraint rentang ini membolehkan Composer memilih versi runtime 1.x terbaru yang kompatibel, bukan mengunci starter pada satu patch; perubahan source runtime harus lebih dulu diterbitkan sebagai tag package agar tersedia di Packagist. Hook `post-create-project-cmd` memasang launcher npm global `@davingm/laravel-cli` bila belum ada. Composer plugin sendiri tidak membuat file `artisan` di root atau mengubah `PATH`. Path repository serta opsi versi package mengikuti [dokumentasi Composer](https://getcomposer.org/doc/05-repositories.md#path).

## Alur command tanpa file `artisan` di root

Bootstrap PHP berada di source `packages/laravel/.laravel/artisan.php` dan terpasang sebagai `.laravel/artisan.php`. CLI `packages/laravel/.laravel/cli.js` menemukenali project dari `composer.json`, `bootstrap/app.php`, dan runtime `.laravel/`, lalu meneruskan command Laravel ke bootstrap tersembunyi tersebut.

Gunakan:

```bash
node .laravel/cli.js route:list
node .laravel/cli.js test --compact
composer run dev
```

Launcher npm global `artisan` meneruskan command ke CLI runtime project. Saat mengubah runtime CLI, periksa juga Composer scripts, command build/preview, dokumentasi, dan workflow CI agar tidak bergantung pada file root.

## Rilis runtime package ke repository terpisah

Package Composer bernama `davingm/laravel-runtime`, dengan repository tujuan [github.com/davingm/laravel-runtime](https://github.com/davingm/laravel-runtime). Perintah Git berikut dijalankan dari root monorepo di Git Bash, WSL, Linux, atau macOS.

### 1. Commit source monorepo

Pastikan perubahan source sudah di-commit. Bila perubahan aplikasi atau dokumentasi ikut dirilis di monorepo, stage file terkait secara eksplisit.

```bash
git status --short
git add packages/laravel
git commit -m "Update Laravel runtime"
git push origin main
```

### 2. Pastikan remote package tersedia

Tambahkan remote satu kali jika belum ada:

```bash
git remote add runtime https://github.com/davingm/laravel-runtime.git
git remote -v
```

Pastikan remote `runtime` mengarah ke repository package. Remote `origin` tetap repository monorepo.

### 3. Split folder dan push branch package

Periksa tag yang sudah terbit dan pilih versi baru yang belum digunakan. Dari root monorepo:

```bash
split_commit=$(git subtree split --prefix=packages/laravel) || exit 1
test -n "$split_commit" || { echo "git subtree split gagal" >&2; exit 1; }

git show --stat --oneline "$split_commit"
git push runtime "$split_commit:refs/heads/main"
```

Git Subtree membuat riwayat package dengan isi folder tersebut di root repository tujuan. Lihat [dokumentasi Git Subtree](https://github.com/git/git/blob/master/contrib/subtree/git-subtree.adoc).

### 4. Push tag release

Ganti `vX.Y.Z` dengan versi baru yang belum pernah dipakai:

```bash
git push runtime "$split_commit:refs/tags/vX.Y.Z"
git ls-remote runtime refs/heads/main "refs/tags/vX.Y.Z"
```

Tag release berada di repository `laravel-runtime`. Jangan menimpa tag lama atau memakai force push untuk melewati penolakan remote.

### 5. Verifikasi rilis

Pastikan repo package menampilkan `composer.json`, `src/`, dan `.laravel/` pada root. Periksa sinkronisasi Packagist, lalu uji package dari checkout/salinan starter tanpa path repository lokal:

```bash
composer clear-cache
composer update davingm/laravel-runtime --with-dependencies --no-interaction
composer show davingm/laravel-runtime
composer install --no-interaction --prefer-dist
test -f .laravel/cli.js
test -f .laravel/artisan.php
test -f .laravel/frontend/framework.ts
node .laravel/cli.js test --compact
npm ci --ignore-scripts
npm ls @davingm/welcome
npm run build
```

Lockfile npm pada starter harus menunjuk ke paket registry, bukan path lokal seperti `../packages/welcome`, karena folder `packages/` tidak ada pada project hasil `create-project`.

Untuk validasi manifest, Packagist, dan troubleshooting rilis, lihat [panduan publish runtime package](./publish-runtime-package.md).

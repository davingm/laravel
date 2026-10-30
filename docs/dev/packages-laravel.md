# Struktur `packages/laravel`

`packages/laravel/` adalah source package framework yang dikembangkan di atas Laravel. Folder ini memuat Composer plugin dan source runtime yang dipasang ke aplikasi. Isi package ini tidak sama dengan struktur aplikasi di `playground/`.

## Peta folder saat ini

Path pada tabel relatif terhadap `packages/laravel/`. Tujuan folder yang masih kosong adalah pengelompokan sementara berdasarkan namanya, bukan kontrak arsitektur.

| Path | Isi saat ini | Kegunaan atau status |
| --- | --- | --- |
| `composer.json` | Manifest `davingm/laravel-runtime`, autoload PSR-4 `Davingm\LaravelRuntime\` dari `src/`, dan deklarasi Composer plugin. | Konfigurasi package. Pertahankan atau ubah sesuai kebutuhan package Composer. |
| `src/` | Plugin Composer dan source PHP framework yang di-autoload. | Kode framework aktif berada di package/vendor; bukan berkas aplikasi dan bukan isi `.laravel/`. |
| `src/app/` | `commands/`, `helpers/`, `middleware/`, `providers/`, `routes/`, dan `support/`. | Integrasi aplikasi Laravel seperti service provider, routing halaman, frontend, middleware, helper, dan command framework. |
| `src/compiler/` | Subfolder kosong: `plugin/`, `runtime/`. | Kandidat area kompilasi; belum memiliki implementasi. |
| `src/core/` | Subfolder kosong: `plugin/`, `utils/`. | Kandidat area komponen inti; belum memiliki implementasi. |
| `src/imports/` | Kosong. | Belum ada tanggung jawab atau API yang ditetapkan. |
| `src/runtime/` | Kosong. | Belum ada tanggung jawab atau API yang ditetapkan. |
| `src/cli/` | Manifest package npm `@davingm/laravel-cli` dan executable launcher global `artisan`. | Entry point global yang mencari project aktif lalu meneruskan command ke `.laravel/cli.js` project tersebut. Source package berada di dalam `src/`, lalu dipublikasikan terpisah dari runtime Composer. |
| `.laravel/` | Berkas runtime, CLI, generator, template, JavaScript framework, dan manifest npm; rincian di bawah. | Source runtime yang memang disalin oleh Composer plugin ke folder `.laravel/` di root aplikasi. |
| `LICENSE` | Lisensi package. | Metadata distribusi. |
| `README.md` | Ringkasan singkat package dan mekanisme instalasi runtime. | Dokumentasi package. |

Folder kosong tersebut bukan fondasi yang wajib diikuti. Saat menambahkan fitur, gunakan pengelompokan yang paling jelas untuk tanggung jawabnya; boleh membuat folder dengan nama lain, memindahkan source, atau tidak membuat folder terpisah bila tidak diperlukan. Jangan mempertahankan folder kosong hanya demi memenuhi peta di atas.

## Isi `.laravel/` package

Berikut source runtime package yang saat ini disalin ke aplikasi:

| Path | Isi dan tanggung jawab saat ini |
| --- | --- |
| `artisan.php`, `cli.js` | Bootstrap command internal dan entry CLI runtime. |
| `cli/` | Pendukung CLI, termasuk telemetry request dan terminal panel. |
| `crud/` | Stub controller, model, form, dan halaman CRUD. |
| `frontend/framework.js` | JavaScript framework yang digunakan aplikasi. |
| `generators/` | Implementasi command generator halaman, controller, migration, CRUD, dashboard, roles, dan command terkait. |
| `layouts/` | Stub layout navbar dan sidebar untuk generator. |
| `role/` | Stub middleware role. |
| `LICENSE` | Lisensi yang ikut bersama runtime. |

Source launcher global berada di `packages/laravel/src/cli/`. Composer plugin tidak memasang executable global atau mengubah `PATH`; hook `post-create-project-cmd` menjalankan installer npm yang memeriksa versi global dan melewati instalasi bila launcher sudah tersedia.

Runtime package berada di `packages/laravel/.laravel/`. Saat diinstal, Composer plugin menyalin isinya ke `.laravel/` pada root aplikasi. Folder tujuan di aplikasi merupakan output lokal yang diabaikan Git; jangan mengedit salinan tersebut sebagai source.

## Batas `src/` dan `.laravel/`

`FrameworkServiceProvider` didaftarkan melalui Laravel package discovery. Ia mendaftarkan command, middleware, directive Blade, route halaman otomatis, dan endpoint telemetry; aplikasi tidak perlu menyalin kelas tersebut ke `app/` atau mengaturnya di `bootstrap/app.php`.

Kedua lokasi ini mempunyai tujuan dan lokasi instalasi berbeda:

- `packages/laravel/src/` adalah source PHP package Composer. Composer meng-autoload namespace package dari folder ini; saat package terpasang, source tetap berada di package di `vendor/` (atau pada package terpisah jika arsitektur kelak memerlukannya).
- `packages/laravel/.laravel/` berisi berkas runtime yang perlu tersedia di root aplikasi. Composer plugin saat ini hanya menyalin isi folder ini ke `<root-aplikasi>/.laravel/`.
- `src/` tidak boleh disalin ke `<root-aplikasi>/.laravel/`. Jangan menggabungkan kedua mekanisme atau menjadikan `.laravel/` aplikasi sebagai lokasi source utama package.
- Bila aplikasi perlu memakai kode PHP dari `src/`, akses melalui autoload/API package Composer. Bila suatu fitur memang harus menjadi berkas runtime di root aplikasi, tempatkan source runtime-nya pada lokasi package yang sesuai dan perbarui proses instalasinya secara eksplisit, tanpa menyalin `src/` secara keseluruhan.

Nama dan susunan folder internal package dapat berubah seiring kebutuhan. Batas distribusi yang penting adalah source package tetap berada di package/vendor, sementara hanya berkas runtime yang memang diperlukan aplikasi yang dikirim ke `.laravel/`.

## Pengembangan dan distribusi

Uji perubahan package melalui aplikasi di `playground/`, yang memakai path repository lokal ke `packages/laravel/`. Untuk alur pengujian, lihat [Mengembangkan framework melalui playground](./develop-framework-in-playground.md). Untuk split dan rilis package, lihat [Merilis package runtime](./publish-runtime-package.md).

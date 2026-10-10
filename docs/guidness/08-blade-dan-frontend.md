# 8. Blade dan frontend

Frontend bawaan terdiri atas HTML yang dirender Laravel melalui Blade serta asset CSS/JavaScript yang dikelola Vite.

## Blade pages, layouts, dan components

Karena root view disetel ke `src/`, Laravel mencari Blade view di sana:

- Halaman: `src/pages/`.
- Layout: `src/layouts/`.
- Komponen: `src/components/`.

Layout utama `src/layouts/app.blade.php` memuat asset Vite, elemen `#page-view`, section konten, metadata, dan payload.

Contoh halaman:

```blade
@extends('layouts.app')

@section('content')
    <main>
        <h1>Kontak</h1>
        <p>Hubungi tim kami.</p>
    </main>
@endsection
```

Blade dijalankan di server. Gunakan escaping Blade standar untuk data pengguna agar output HTML aman. Komponen anonymous dapat disimpan di `src/components/` dan didaftarkan oleh `AppServiceProvider`.

## Metadata halaman dan payload

Data `title` dan `description` yang diberikan melalui `page()` atau `Frontend::render()` dapat dipakai untuk metadata. Directive `@pageMeta` menghasilkan title dan description pada layout; directive `@payload` menambahkan payload JSON halaman.

Payload dan manifest adalah file runtime di `.laravel/cache/`. Jangan mengedit hasil tersebut sebagai source; ubah halaman atau kode `Frontend`, lalu generate ulang bila diperlukan.

## JavaScript

Entry JavaScript saat ini adalah `src/assets/js/app.js`. Ia:

- Membaca JSON payload dan mengekspos API di `window.__AUTO_LARAVEL__`.
- Menangani link `data-navigate` dengan mengambil HTML dan mengganti `#page-view`.
- Menjalankan navigasi penuh sebagai fallback bila navigasi ringan gagal.
- Mendukung form konfirmasi `data-confirm`.
- Memfilter baris tabel untuk input `data-table-search`.
- Mengatur delay elemen yang memakai `data-reveal`.

Navigasi ringan hanya berlaku pada link yang ditandai. Link biasa tetap dapat dinavigasi browser. Event `auto:navigated` dikirim setelah navigasi ringan berhasil.

## CSS dan Tailwind

Entry CSS adalah `src/assets/css/app.css`. File ini mengimpor Tailwind CSS dan memuat beberapa rule untuk halaman CRUD hasil generator.

Tambahkan style pada source, bukan di `public/build/`. Jika menambahkan lokasi template/stub baru yang menggunakan class Tailwind, pastikan lokasinya tercakup dalam directive `@source`, jika tidak class dapat terhapus saat build.

## Menambahkan package frontend

Package JavaScript/CSS dipasang melalui npm dari root aplikasi. Bedakan package library biasa dengan plugin Vite: library dipakai di source browser, sedangkan plugin Vite memperluas proses development/build dan dikonfigurasi di `vite.config.js`.

### Library JavaScript

Pasang package yang akan dipakai aplikasi:

```bash
npm install nama-package
```

Impor package dari `src/assets/js/app.js`, sesuai nama export pada dokumentasi package:

```js
import { fungsiYangDipakai } from 'nama-package';

fungsiYangDipakai();
```

Vite akan membundle import tersebut ke asset aplikasi. Package runtime semacam ini dicatat sebagai dependency aplikasi oleh npm.

### Package CSS

Pasang package CSS:

```bash
npm install nama-package-css
```

Lalu impor file CSS yang didokumentasikan package di `src/assets/css/app.css`:

```css
@import 'tailwindcss';
@import 'nama-package-css/dist/style.css';
```

Path CSS berbeda antar-package. Gunakan path import resmi package tersebut. Jika package meminta urutan import tertentu, ikuti petunjuknya dan pastikan Tailwind directive/source project tetap ada.

### Plugin Vite

Plugin Vite bukan sekadar package yang di-import ke JavaScript browser. Pasang sebagai development dependency:

```bash
npm install --save-dev nama-plugin-vite
```

Import plugin dan tambahkan pemanggilannya ke array `plugins` yang sudah ada di `vite.config.js`. Contoh struktur:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import pluginTambahan from 'nama-plugin-vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['src/assets/css/app.css', 'src/assets/js/app.js'],
            refresh: ['src/**/*.blade.php'],
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
        pluginTambahan(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

Ganti `nama-plugin-vite` dan `pluginTambahan` dengan nama package dan export plugin yang sebenarnya. Bentuk pemanggilan plugin dapat berbeda; ikuti dokumentasi package. Pertahankan Laravel plugin, dua input entry, font, Tailwind, dan pengaturan watcher yang sudah ada kecuali dokumentasi plugin menyatakan perlu penyesuaian. Jangan mendaftarkan library browser biasa sebagai plugin Vite.

Jika menambah file JavaScript atau CSS sebagai **entry terpisah**, tambahkan path-nya ke `input` Laravel Vite plugin dan muat entry tersebut dari layout menggunakan `@vite`. Jika package hanya di-import oleh `app.js` atau `app.css`, biasanya tidak perlu menambah entry baru.

Sesudah memasang package, npm memperbarui `package.json` dan `package-lock.json`. Simpan perubahan keduanya agar instalasi berikutnya menggunakan dependency yang sama. Jalankan build untuk memeriksa integrasi:

```bash
npm run build
```

Lihat panduan resmi [Laravel Vite](https://laravel.com/docs/13.x/vite), [Vite Plugin API](https://vite.dev/guide/api-plugin.html), serta [npm install](https://docs.npmjs.com/cli/v11/commands/npm-install) untuk detail konfigurasi dan package yang digunakan.

## Vite

`vite.config.js` mendaftarkan:

- `src/assets/css/app.css`
- `src/assets/js/app.js`

Saat development, `artisan dev` menjalankan Vite dev server. Build production dilakukan dengan:

```bash
npm run build
```

Output di `public/build/` dimuat oleh layout melalui directive `@vite`.

## Langkah berikutnya

Untuk menyiapkan schema dan data, lanjut ke [Bab 9: Database dan migration](./09-database-dan-migration.md).

---

[Kembali: 7. Backend](./07-backend.md) · [Indeks dokumentasi](../README.md) · Lanjut: [9. Database dan migration](./09-database-dan-migration.md)

# 8. Blade dan frontend

Frontend bawaan terdiri atas HTML yang dirender Laravel melalui Blade serta asset CSS dan TypeScript yang dikelola Vite. Kode interaksi aplikasi ditulis dalam TypeScript; JavaScript internal framework tetap disediakan terpisah oleh runtime.

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

Blade dijalankan di server. Gunakan escaping Blade standar untuk data pengguna agar output HTML aman. Komponen anonymous dapat disimpan di `src/components/` dan didaftarkan otomatis oleh framework.

## Metadata halaman dan payload

Data `title` dan `description` yang diberikan melalui `page()` atau `Frontend::render()` dapat dipakai untuk metadata. Directive `@pageMeta` menghasilkan title dan description pada layout; directive `@payload` menambahkan payload JSON halaman.

Payload dan manifest adalah file runtime di `.laravel/cache/`. Jangan mengedit hasil tersebut sebagai source; ubah halaman atau kode `Frontend`, lalu generate ulang bila diperlukan.

## TypeScript aplikasi dan runtime framework

Kode JavaScript framework berada di `packages/laravel/.laravel/frontend/framework.js`, didistribusikan melalui Composer, dan disalin ke `.laravel/frontend/framework.js`. Layout dan Vite memuat hasil generate itu sebagai entry terpisah. Jangan menyalin atau mengedit kode framework ke dalam `src/`; Composer dapat membuat ulang runtime internal.

`src/assets/ts/app.ts` adalah entry wajib untuk interaksi khusus aplikasi. Tulis kode browser aplikasi dalam TypeScript dan jalankan `npm run typecheck` untuk memeriksa tipenya. Vite mentranspilasi TypeScript saat build. Perilaku framework berikut tetap berada di entry internal:

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

Package frontend dipasang melalui npm dari root aplikasi. Bedakan package library dengan plugin Vite: library dapat diimpor dari source TypeScript aplikasi, sedangkan plugin Vite memperluas proses development/build dan dikonfigurasi di `vite.config.ts`.

### Library JavaScript

Pasang package yang akan dipakai aplikasi:

```bash
npm install nama-package
```

Impor package dari `src/assets/ts/app.ts`, sesuai nama export pada dokumentasi package:

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

Import plugin dan tambahkan pemanggilannya ke array `plugins` yang sudah ada di `vite.config.ts`. Contoh struktur:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import pluginTambahan from 'nama-plugin-vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'src/assets/css/app.css',
                '.laravel/frontend/framework.js',
                'src/assets/ts/app.ts',
            ],
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

Ganti `nama-plugin-vite` dan `pluginTambahan` dengan nama package dan export plugin yang sebenarnya. Bentuk pemanggilan plugin dapat berbeda; ikuti dokumentasi package. Pertahankan entry CSS aplikasi, entry JavaScript internal `.laravel/frontend/framework.js`, entry TypeScript aplikasi, font, Tailwind, dan pengaturan watcher yang sudah ada kecuali dokumentasi plugin menyatakan perlu penyesuaian. Jangan mendaftarkan library browser biasa sebagai plugin Vite.

Jika menambah file TypeScript atau CSS sebagai **entry terpisah**, tambahkan path-nya ke `input` Laravel Vite plugin dan muat entry tersebut dari layout menggunakan `@vite`. Jika package hanya di-import oleh `app.ts` atau `app.css`, biasanya tidak perlu menambah entry baru.

Sesudah memasang package, npm memperbarui `package.json` dan `package-lock.json`. Simpan perubahan keduanya agar instalasi berikutnya menggunakan dependency yang sama. Jalankan build untuk memeriksa integrasi:

```bash
npm run build
```

Lihat panduan resmi [Laravel Vite](https://laravel.com/docs/13.x/vite), [Vite Plugin API](https://vite.dev/guide/api-plugin.html), serta [npm install](https://docs.npmjs.com/cli/v11/commands/npm-install) untuk detail konfigurasi dan package yang digunakan.

## Vite

`vite.config.ts` mendaftarkan asset aplikasi dan entry runtime framework:

- `src/assets/css/app.css`
- `.laravel/frontend/framework.js` (source package berada di `packages/laravel/.laravel/frontend/framework.js`)
- `src/assets/ts/app.ts`

Konfigurasi `tsconfig.json` memeriksa source aplikasi di `src/assets/ts/`. Jalankan `npm run typecheck` untuk pemeriksaan tipe dan `npm run build` untuk membangun asset.

Saat development, `artisan dev` menjalankan Vite dev server. Build production dilakukan dengan:

```bash
npm run build
```

Output di `public/build/` dimuat oleh layout melalui directive `@vite`.

## Langkah berikutnya

Untuk menyiapkan schema dan data, lanjut ke [Bab 9: Database dan migration](./09-database-dan-migration.md).

---

[Kembali: 7. Backend](./07-backend.md) · [Indeks dokumentasi](../README.md) · Lanjut: [9. Database dan migration](./09-database-dan-migration.md)

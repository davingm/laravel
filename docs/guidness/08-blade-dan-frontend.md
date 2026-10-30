# 8. Blade dan frontend

Frontend bawaan terdiri atas HTML yang dirender Laravel melalui Blade serta asset CSS dan TypeScript yang dikelola Vite. Kode interaksi aplikasi ditulis dalam TypeScript; JavaScript internal framework tetap disediakan terpisah oleh runtime.

## Blade pages, layouts, dan components

Karena root view disetel ke `src/`, Laravel mencari Blade view di sana:

- Halaman: `src/pages/`.
- Layout: `src/layouts/`.
- Komponen: `src/components/`.

Entry aplikasi `src/index.blade.php` memilih layout dan menempatkan halaman melalui router framework:

```blade
<pages>
    <laravelLayoute>
        <PageRouter />
    </laravelLayoute>
</pages>
```

`src/layouts/default.blade.php` adalah layout default dan mengurus dokumen HTML, asset Vite, metadata, serta payload. File lain di `src/layouts/` juga tersedia sebagai layout bernama. Pilih layout lain melalui opsi `layout` pada `PageRouter::register()` atau argumen ketiga helper `page()`, misalnya `page('dashboard', $data, 'layouts.sidebar')`.

Contoh halaman:

```blade
@extends('layouts.default')

@section('content')
    <main>
        <h1>Kontak</h1>
        <p>Hubungi tim kami.</p>
    </main>
@endsection
```

Blade dijalankan di server. Gunakan escaping Blade standar untuk data pengguna agar output HTML aman. Komponen anonymous dapat disimpan di `src/components/` dan didaftarkan otomatis oleh framework.

### Halaman satu file

Halaman dapat ditulis dalam satu file `.blade.php` tanpa `@extends`, `@section`, atau kerangka dokumen HTML. Gunakan `<script setup lang="ts">` untuk interaksi browser, `<style scoped>` bila halaman memiliki CSS sendiri, dan `<pages>` untuk membungkus HTML:

```blade
<script setup lang="ts">
const button = document.querySelector<HTMLButtonElement>('[data-counter]');
const output = document.querySelector<HTMLOutputElement>('[data-count]');
let count = 0;

button?.addEventListener('click', () => {
    count += 1;
    if (output) output.textContent = String(count);
});
</script>

<style scoped>
.counter { display: flex; gap: 1rem; }
</style>

<pages>
    <section class="counter">
        <button type="button" data-counter>Tambah</button>
        <output data-count>0</output>
    </section>
</pages>
```

TypeScript di dalam script adalah kode browser biasa, termasuk penggunaan API DOM seperti `addEventListener`; framework tidak menambahkan event directive. Import TypeScript diperbolehkan, tetapi export tidak digunakan dalam blok setup. Ekspresi dan directive Blade di markup, seperti `{{ $name }}` dan `@if`, tetap diproses Laravel di server. Framework mengurus layout dokumen dan metadata default: title dari nama halaman dan aplikasi, description dari nama halaman, serta gambar Open Graph dari `public/Laravel.svg`. Layout juga mengeluarkan metadata Open Graph dan Twitter. Route yang memakai `page()` dapat memberi `title`, `description`, `ogTitle`, `ogDescription`, `ogImage`, dan `ogUrl` untuk mengganti nilai default.

Compiler mengumpulkan TypeScript dan CSS halaman sebelum Vite membangun asset. `<pages>` adalah pembungkus source saja: saat dirender, compiler menggantinya dengan `<div data-page-scope="…">` standar agar tidak ada custom tag yang bocor ke HTML. CSS pada `<style scoped>` dan script halaman memakai atribut scope tersebut. Hasil kompilasi berada di `.laravel/generated/`; jangan diedit manual. Dalam production, middleware framework juga memadatkan HTML response menjadi satu baris. Halaman Blade konvensional dan komponen anonymous tetap didukung; komponen dapat dipakai di dalam `<pages>` dan hasilnya dirender di dalam layout yang dipilih.

## Metadata halaman dan payload

Data `title` dan `description` yang diberikan melalui `page()` atau `Frontend::render()` dapat dipakai untuk metadata. Directive `@pageMeta` menghasilkan title dan description pada layout; directive `@payload` menambahkan payload JSON halaman.

Payload dan manifest adalah file runtime di `.laravel/cache/`. Jangan mengedit hasil tersebut sebagai source; ubah halaman atau kode `Frontend`, lalu generate ulang bila diperlukan.

## TypeScript aplikasi dan runtime framework

Kode JavaScript framework berada di `packages/laravel/.laravel/frontend/framework.js`, didistribusikan melalui Composer, dan disalin ke `.laravel/frontend/framework.js`. Layout dan Vite memuat hasil generate itu sebagai entry terpisah. Jangan menyalin atau mengedit kode framework ke dalam `src/`; Composer dapat membuat ulang runtime internal.

`src/assets/ts/app.ts` adalah entry TypeScript bersama untuk interaksi aplikasi. Halaman satu file dapat memiliki TypeScript sendiri melalui `<script setup lang="ts">`; `npm run build` memeriksa tipe seluruh source sebelum membangun asset. Perilaku framework berikut tetap berada di entry internal:

- Membaca JSON payload dan mengekspos API di `window.__AUTO_LARAVEL__`.
- Menangani link `data-navigate` dengan mengambil HTML dan mengganti `#page-view`.
- Menjalankan navigasi penuh sebagai fallback bila navigasi ringan gagal.
- Mendukung form konfirmasi `data-confirm`.
- Memfilter baris tabel untuk input `data-table-search`.
- Mengatur delay elemen yang memakai `data-reveal`.

Navigasi ringan hanya berlaku pada link yang ditandai. Link biasa tetap dapat dinavigasi browser. Event `auto:navigated` dikirim setelah navigasi ringan berhasil.

## CSS dan Tailwind

Entry CSS aplikasi adalah `src/assets/css/app.css`; default-nya hanya mengimpor Tailwind CSS dan mendaftarkan source Blade aplikasi serta view pagination Laravel. Halaman dan generator menggunakan utility Tailwind langsung pada markup, jadi style CRUD tidak dibundel sebagai CSS global framework. Untuk tampilan khusus halaman, gunakan utility Tailwind atau `<style scoped>`.

Tambahkan style pada source, bukan di `public/build/`. Jika menambahkan lokasi template/stub baru yang menggunakan class Tailwind, pastikan lokasinya tercakup dalam directive `@source`, jika tidak class dapat terhapus saat build.

## Menambahkan package frontend

Package frontend dipasang melalui npm dari root aplikasi. Bedakan package library dengan plugin Vite: library dapat diimpor dari source TypeScript aplikasi, sedangkan plugin Vite memperluas proses development/build dan dikonfigurasi di `vite.config.ts`.

### Library JavaScript

Pasang package yang akan dipakai aplikasi:

```bash
npm install nama-package
```

Impor package dari `src/assets/ts/app.ts`, sesuai nama export pada dokumentasi package:

```ts
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
import { laravelPages } from './vendor/davingm/laravel-runtime/src/Compiler/Plugin/vite.mjs';
import pluginTambahan from 'nama-plugin-vite';

export default defineConfig({
    plugins: [
        laravelPages(),
        laravel({
            input: [
                'src/assets/css/app.css',
                '.laravel/generated/pages.css',
                '.laravel/frontend/framework.js',
                'src/assets/ts/app.ts',
                '.laravel/generated/pages.ts',
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

Ganti `nama-plugin-vite` dan `pluginTambahan` dengan nama package dan export plugin yang sebenarnya. Bentuk pemanggilan plugin dapat berbeda; ikuti dokumentasi package. Pertahankan `laravelPages()` sebelum plugin Laravel Vite agar entry halaman dibuat, entry CSS aplikasi, entry JavaScript internal `.laravel/frontend/framework.js`, entry TypeScript aplikasi, kedua entry `.laravel/generated/pages.*`, font, Tailwind, dan pengaturan watcher yang sudah ada. Jangan mendaftarkan library browser biasa sebagai plugin Vite.

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
- `.laravel/generated/pages.css` dan `.laravel/generated/pages.ts` (dibuat dari blok halaman satu file oleh plugin Vite runtime)

Konfigurasi `tsconfig.json` memeriksa source aplikasi di `src/assets/ts/` dan hasil kompilasi TypeScript halaman. `npm run build` menjalankan pemeriksaan tipe sebelum membangun asset.

Saat development, `artisan dev` menjalankan Vite dev server. Build production dilakukan dengan:

```bash
npm run build
```

Output di `public/build/` dimuat oleh layout melalui directive `@vite`.

## Langkah berikutnya

Untuk menyiapkan schema dan data, lanjut ke [Bab 9: Database dan migration](./09-database-dan-migration.md).

---

[Kembali: 7. Backend](./07-backend.md) · [Indeks dokumentasi](../README.md) · Lanjut: [9. Database dan migration](./09-database-dan-migration.md)

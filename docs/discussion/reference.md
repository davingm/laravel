# davingm/laravel — Reference Documentation

> **Status: referensi lama, belum menjadi panduan siap pakai.** Dokumen ini masih memuat nama `davingm/laravel` dan API frontend lama. Cocokkan setiap command, path, dan API dengan implementasi saat ini sebelum menggunakannya sebagai acuan.

> Framework documentation for the **davingm** Laravel starter.
> PHP >= 8.3 · Node.js >= 20.19 or >= 22.12 · Laravel 13

---

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [The artisan CLI](#the-artisan-cli)
4. [Artisan Commands](#artisan-commands)
5. [File-Based Routing — PageRouter](#file-based-routing--pagerouter)
6. [Frontend Engine](#frontend-engine)
7. [Blade Directives](#blade-directives)
8. [SEO Head Management](#seo-head-management)
9. [Soft Navigation](#soft-navigation)
10. [Layout and Components](#layout-and-components)
11. [Cache and Runtime Files](#cache-and-runtime-files)
12. [Setup and Installation](#setup-and-installation)

---

## Introduction

`davingm/laravel` adalah Laravel starter kit yang membawa konvensi modern ala Nuxt ke ekosistem Blade tanpa Vue, tanpa build kompleks. Framework ini menambahkan:

- **File-based routing** — buat file di `src/pages/`, route terdaftar otomatis
- **Frontend payload system** — tiap halaman punya JSON state yang bisa dibaca JS
- **SEO head management** — mirip `useSeoMeta()` di Nuxt 3, langsung di Blade
- **Custom `artisan` CLI** — wrapper Node.js yang menjalankan dev server, queue, dan Vite sekaligus
- **Scaffolding commands** — `make:all`, `make:page`, `make:controller` dengan opinionated stubs

---

## Getting Started

```bash
# Buat project baru
composer create-project davingm/laravel nama-proyek
cd nama-proyek

# Mulai development
artisan dev
```

Setelah `composer create-project`, Composer menyiapkan database, menginstall npm dependencies dari `package-lock.json`, membangun asset frontend, dan memasang CLI `artisan` secara global via `setup.js`. Launcher mencari `.laravel/cli.js` dari direktori kerja saat ini ke direktori induk, sehingga satu instalasi global bisa digunakan di beberapa project. Jalankan `artisan dev` dari direktori project atau subdirektorinya.

Jika launcher global yang sudah ada masih menunjuk ke lokasi project lama, jalankan ulang setup dari root project:

```bash
node .laravel/setup.js
```

---

## The artisan CLI

`artisan` adalah wrapper Node.js (`.laravel/cli.js`) yang memperluas perintah standar `php artisan`. Berbeda dengan `php artisan` biasa, CLI ini:

- Menjalankan beberapa proses sekaligus (`php artisan serve`, `queue:work`, `npm run dev`)
- Menampilkan output dengan color-coding dan formatting yang bersih
- Mengelola preview mode (minifikasi HTML)

### artisan dev

```bash
artisan dev
```

Memulai environment development lengkap dalam satu terminal:

| Proses | Keterangan |
|---|---|
| `php artisan serve` | Laravel HTTP server di port 8000 (atau `APP_PORT`) |
| `php artisan queue:work` | Queue worker dengan retry 3x |
| `npm run dev` | Vite dev server untuk hot module replacement |

Output diberi warna berdasarkan kecepatan response:

- **Hijau** — kurang dari 200ms
- **Oranye** — 200ms sampai 1000ms
- **Merah** — lebih dari 1000ms

Ganti port default:

```bash
APP_PORT=3000 artisan dev
```

---

### artisan build

```bash
artisan build
```

Alias untuk `php artisan build`. Menjalankan pipeline build production lengkap.

---

### artisan preview

```bash
artisan preview
artisan preview --port=9000
```

Alias untuk `php artisan preview`. Menjalankan app dalam mode production preview tanpa mengubah `.env`.

---

### artisan passthrough

Semua perintah lain diteruskan langsung ke `php artisan`:

```bash
artisan migrate
artisan make:model Post
artisan route:list
artisan tinker
artisan db:seed
artisan <any-artisan-command>
```

---

## Artisan Commands

### make:page

Membuat halaman Blade baru di `resources/views/pages/` dengan konfigurasi SEO terpisah dari konten.

```bash
php artisan make:page <path>
php artisan make:page <path> --force
```

**Argumen:**

| Argumen | Keterangan |
|---|---|
| `name` | Path halaman, e.g. `about`, `blog/post`, `siswa/detail` |

**Opsi:**

| Opsi | Keterangan |
|---|---|
| `--force` | Timpa file yang sudah ada |

**Contoh:**

```bash
php artisan make:page about
# Hasil: src/pages/about.blade.php
# Route: GET /about

php artisan make:page blog/post
# Hasil: src/pages/blog/post.blade.php
# Route: GET /blog/post

php artisan make:page siswa/detail --force
# Menimpa file yang sudah ada
```

**Isi file yang dihasilkan:**

Setiap halaman hasil `make:page` sudah include:

```blade
@php
        $seo = [
                'title' => 'About',
                'description' => 'Halaman About.',
                'ogTitle' => 'About',
                'ogDescription' => 'Halaman About.',
                'ogImage' => asset('images/og-image.jpg'),
        ];
@endphp

@section('seo')
        <x-seo-meta :seo="$seo" />
@endsection

@section('content')
        {{-- Konten utama halaman --}}
@endsection
```

SEO dirender oleh komponen `x-seo-meta`; isi `<head>` tidak perlu ditulis ulang pada setiap halaman.

**Perilaku route cache:**

- Jika route cache belum ada, command menjalankan `route:clear` agar route halaman baru langsung terdeteksi.
- Jika route cache sudah ada, command mempertahankannya dan menampilkan peringatan. Untuk mendaftarkan halaman baru, jalankan secara manual:

```bash
php artisan route:clear
php artisan route:cache
```

---

### make:controller

Membuat controller dengan stub opinionated yang sudah meng-import `Frontend` class.

```bash
php artisan make:controller <name>
php artisan make:controller <name> --resource
php artisan make:controller <name> --resource --model=<Model>
php artisan make:controller <name> --plain
```

**Argumen:**

| Argumen | Keterangan |
|---|---|
| `name` | Nama controller, e.g. `BarangController` atau `Barang` (suffix otomatis ditambah) |

**Opsi:**

| Opsi | Keterangan |
|---|---|
| `--resource` | Generate resource controller dengan 7 method CRUD |
| `--model=` | Model untuk type-hints. Jika tidak diisi, diinfer dari nama controller |
| `--plain` | Generate controller kosong tanpa method |

**Contoh:**

```bash
php artisan make:controller PostController --resource --model=Post
# Hasil: app/Http/Controllers/PostController.php
```

**Stub resource controller** yang dihasilkan sudah include:

- Import `Frontend`, form request classes, model, dan response types
- Method `index()` — paginate 10, render via `Frontend::render()`
- Method `create()` — render form
- Method `store()` — validasi, simpan, redirect dengan flash message
- Method `show()` — render detail
- Method `edit()` — render form edit
- Method `update()` — validasi, update, redirect
- Method `destroy()` — hapus, redirect ke index

Semua redirect menggunakan named routes (`route('prefix.show', $model)`).

---

### make:all

Scaffold **semua layer** untuk sebuah resource dalam satu perintah.

```bash
php artisan make:all <ModelName>
```

**Argumen:**

| Argumen | Keterangan |
|---|---|
| `name` | Nama model dalam StudlyCase, e.g. `Barang`, `BlogPost` |

**Yang dihasilkan:**

```
app/Models/Barang.php
database/migrations/*_create_barangs_table.php
database/factories/BarangFactory.php
database/seeders/BarangSeeder.php
app/Http/Requests/StoreBarangRequest.php
app/Http/Requests/UpdateBarangRequest.php
app/Http/Controllers/BarangController.php
routes/web.php  (Route::resource diinjek otomatis)
routes/web.php  ('barang' ditambah ke exclude list PageRouter)
```

**Next steps setelah `make:all`:**

1. Isi kolom di file migration
2. Tambah `$fillable` di model
3. Tambah validation rules di `Store/UpdateRequest`
4. Jalankan `php artisan migrate`

---

### build

Pipeline build production lengkap.

```bash
php artisan build
php artisan build --skip-tests
php artisan build --skip-npm
```

**Langkah-langkah yang dijalankan:**

| # | Langkah | Keterangan |
|---|---|---|
| 1 | `artisan test` | Jalankan full test suite. Jika gagal, build dihentikan |
| 2 | `npm run build` | Compile frontend assets via Vite |
| 3 | Clear all caches | `view:clear`, `cache:clear`, `config:clear`, `route:clear`, `event:clear` |
| 4 | Cache config | `config:cache` |
| 5 | Cache routes | `route:cache` |
| 6 | Cache views | `view:cache` |
| 7 | Cache events | `event:cache` |
| 8 | Generate manifest | `Frontend::generateManifest()` |

**Opsi:**

| Opsi | Keterangan |
|---|---|
| `--skip-tests` | Lewati test suite |
| `--skip-npm` | Lewati kompilasi frontend |

---

### preview

Jalankan aplikasi dalam mode production preview tanpa mengubah `.env`.

```bash
php artisan preview
php artisan preview --port=9000
php artisan preview --build
```

**Opsi:**

| Opsi | Default | Keterangan |
|---|---|---|
| `--port` | `8000` | Port server |
| `--build` | — | Jalankan `artisan build` terlebih dahulu |

**Perbedaan dev vs preview:**

| | Dev | Preview |
|---|---|---|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| HTML minifikasi | Tidak | Ya |
| Error detail | Ditampilkan | Disembunyikan |

Preview mode menulis file flag `.laravel/.preview` yang dibaca middleware untuk mengaktifkan minifikasi HTML. File ini otomatis dihapus saat server berhenti.

---

### frontend:generate

Generate ulang frontend manifest secara manual.

```bash
php artisan frontend:generate
php artisan frontend:generate --clear
```

**Opsi:**

| Opsi | Keterangan |
|---|---|
| `--clear` | Hapus view cache sebelum generate ulang |

Manifest disimpan di `.laravel/cache/manifest.json` dan di-ignore Git. Perintah ini dijalankan otomatis setiap `artisan dev` dimulai.

---

## File-Based Routing — PageRouter

`PageRouter` memindai `src/pages/` dan mendaftarkan GET route untuk setiap file Blade secara otomatis. Mirip dengan file-system routing di **Nuxt.js**.

### File Conventions

```
pages/index.blade.php              ->  GET /
pages/home.blade.php               ->  GET /home            (name: pages.home)
pages/about/index.blade.php        ->  GET /about           (name: pages.about)
pages/about/team.blade.php         ->  GET /about/team      (name: pages.about.team)
pages/blog/latest-post.blade.php   ->  GET /blog/latest-post
```

**Partial files** yang diawali dengan `_` tidak diregistrasi sebagai route:

```
pages/barang/_form.blade.php  ->  Tidak ada route (dipakai sebagai @include)
```

### Dynamic Segments

Gunakan bracket `[param]` untuk parameter dinamis:

```
pages/blog/[slug].blade.php      ->  GET /blog/{slug}
pages/users/[id]/edit.blade.php  ->  GET /users/{id}/edit
```

### Exclude Patterns

Resource controller yang punya route sendiri harus diexclude dari PageRouter agar tidak terjadi konflik:

```php
// routes/web.php
PageRouter::register([
    'middleware' => ['web'],
    'exclude' => [
        'barang',    // Exact match — hanya block GET /barang (index)
        'siswa',     // Exact match — hanya block GET /siswa (index)
        'barang/*',  // Wildcard — block /barang DAN semua sub-pages
        'siswa/*',   // Wildcard — block /siswa DAN semua sub-pages
    ],
]);
```

> **Penting:** `'siswa'` tanpa `/*` hanya memblock route index (`/siswa`). Route seperti `/siswa/detail` dan `/siswa/about` **tetap terdaftar** oleh PageRouter dan dapat diakses. Gunakan `'siswa/*'` untuk memblock seluruh tree.

`make:all` otomatis menambahkan entry ke `exclude` saat menginjek `Route::resource` baru.

### Manual Route Overrides

Route manual yang didaftarkan setelah `PageRouter::register()` akan meng-override route otomatis dari file yang sama:

```php
// routes/web.php
PageRouter::register(['middleware' => ['web']]);

// Override halaman home dengan data kustom
Route::get('/home', fn () => page('home', [
    'title'       => config('app.name').' | Welcome',
    'description' => 'Selamat datang di aplikasi kami.',
]))->name('pages.home');
```

---

## Frontend Engine

`App\Support\Frontend` adalah inti dari sistem rendering halaman. Ia mengatur bagaimana view, data, dan payload dikirim ke browser.

### Frontend::render()

```php
use App\Support\Frontend;

Frontend::render(
    string $viewKey,
    ?string $pageKey = null,
    array $data = [],
    ?string $layout = 'layouts.app'
): View
```

**Parameter:**

| Parameter | Tipe | Keterangan |
|---|---|---|
| `$viewKey` | `string` | Dot-notation view key, e.g. `'home'`, `'barang.index'` |
| `$pageKey` | `string\|null` | Key untuk payload/cache. Default: sama dengan `$viewKey` |
| `$data` | `array` | Data yang dipass ke view |
| `$layout` | `string\|null` | Layout yang digunakan. Default: `'layouts.app'` |

**Contoh di controller:**

```php
public function index(): View
{
    return Frontend::render('barang.index', null, [
        'title'       => 'Daftar Barang | '.config('app.name'),
        'description' => 'Kelola semua data barang.',
        'barangs'     => Barang::query()->latest()->paginate(10),
    ]);
}
```

Kunci `title` dan `description` di array `$data` secara otomatis dipetakan ke `$frontendPayload['meta']` dan dibaca oleh `@pageMeta`.

### page() Helper

Shorthand global function untuk `Frontend::render()`, tersedia di mana saja tanpa import:

```php
return page('home');
return page('about.index', ['title' => 'Tentang Kami']);
return page('blog.show', ['post' => $post], 'layouts.minimal');
```

**Signature:**

```php
function page(string $viewKey, array $data = [], ?string $layout = 'layouts.app'): View
```

### Payload System

Setiap kali halaman dirender via `Frontend::render()`, sebuah **payload JSON** ditulis ke disk dan diinjek ke HTML:

```json
{
    "page": "barang.index",
    "url": "http://localhost:8000/barang",
    "path": "barang",
    "data": {
        "title": "Daftar Barang | Laravel",
        "description": "Kelola semua data barang."
    },
    "meta": {
        "title": "Daftar Barang | Laravel",
        "description": "Kelola semua data barang."
    },
    "generated_at": "2026-09-27T03:00:00+00:00"
}
```

Payload diakses dari JavaScript via `window.__DAVINGM__.payload`:

```js
const { payload } = window.__DAVINGM__;
console.log(payload.page);        // "barang.index"
console.log(payload.meta.title);  // "Daftar Barang | Laravel"
```

Baca payload dari PHP:

```php
$payload = Frontend::payload('barang.index');
```

### Frontend Manifest

Manifest adalah daftar semua halaman yang terdaftar, disimpan di `.laravel/cache/manifest.json`:

```json
{
    "version": 1,
    "generated_at": "2026-09-27T03:00:00+00:00",
    "pages": {
        "home": "pages.home",
        "about": "pages.about",
        "barang.index": "pages.barang.index"
    }
}
```

Di-generate otomatis saat `artisan dev` dimulai, dan bisa di-generate ulang manual:

```bash
php artisan frontend:generate
```

---

## Blade Directives

### @pageMeta

Menulis `<title>` dan `<meta name="description">` dari payload halaman.

```blade
{{-- Di layouts/app.blade.php --}}
@pageMeta
```

Menghasilkan:

```html
<title>Daftar Barang | Laravel</title>
<meta name="description" content="Kelola semua data barang.">
```

Directive ini hanya bekerja saat halaman dirender via `Frontend::render()` atau `page()`. Jika halaman menggunakan `@section('seo')`, `@pageMeta` di layout otomatis diabaikan karena section `seo` meng-override seluruh blok meta.

---

### @payload

Menginjek frontend payload sebagai JSON ke dalam HTML, sebelum `</body>`:

```blade
@payload
```

Menghasilkan:

```html
<script type="application/json" data-page-payload>{"page":"home",...}</script>
```

Data ini dibaca oleh `app.js` dan tersedia di `window.__DAVINGM__.payload`.

---

### @navigate

Helper untuk menambahkan atribut `data-navigate` pada link:

```blade
<a href="{{ route('barang.index') }}" @navigate(route('barang.index'))>
    Daftar Barang
</a>
```

Menghasilkan:

```html
<a href="/barang" data-navigate="/barang">Daftar Barang</a>
```

Atau lebih eksplisit tanpa directive:

```blade
<a href="{{ route('barang.index') }}" data-navigate="{{ route('barang.index') }}">
    Daftar Barang
</a>
```

---

## SEO Head Management

Sistem SEO di davingm terinspirasi dari `useSeoMeta()` dan `useHead()` di **Nuxt 3** — tiap halaman mengontrol penuh konten `<head>` miliknya.

### @section('seo')

Override penuh blok `<head>` untuk halaman tersebut:

```blade
@section('seo')
    <title>Daftar Barang | MyApp</title>
    <meta name="description" content="Kelola semua barang.">
    <meta property="og:title" content="Daftar Barang">
    <meta property="og:image" content="{{ asset('images/barang-og.jpg') }}">
    <script type="application/ld+json">
    { "@context": "https://schema.org", "@type": "WebPage" }
    </script>
@endsection
```

Setiap halaman hasil `make:page` membuat object `$seo` dan merendernya dengan `<x-seo-meta :seo="$seo" />`. Field yang didukung: `title`, `description`, `ogTitle`, `ogDescription`, `ogImage`, dan `articleAuthor`. Kamu dapat mengubah atau menambahkan field sesuai kebutuhan halaman.

### @section('title')

Cara cepat untuk override hanya `<title>` tanpa perlu menulis `@section('seo')` full:

```blade
@section('title', 'Detail Barang')
{{-- Menghasilkan: <title>Detail Barang | MyApp</title> --}}
```

### @stack('head')

Inject elemen `<head>` tambahan dari halaman tanpa harus meng-override seluruh `@section('seo')`:

```blade
@push('head')
    <link rel="preload" href="{{ asset('fonts/custom.woff2') }}" as="font">
    <meta name="theme-color" content="#d7ed74">
@endpush
```

**Prioritas resolusi head** (tertinggi ke terendah):

```
1. @section('seo')   -> Override penuh, menggantikan semua
2. @section('title') -> Hanya override <title>
3. @pageMeta         -> Dari data Frontend::render()
4. @stack('head')    -> Append ke bagian bawah <head>
```

---

## Soft Navigation

Davingm menggunakan navigasi client-side ringan berbasis `fetch`, tanpa Vue, tanpa React Router, tanpa library besar.

### Cara kerja

Tambahkan `data-navigate` pada link untuk mengaktifkan soft navigation:

```blade
<a href="{{ route('home') }}" data-navigate="{{ route('home') }}">
    Home
</a>
```

Saat diklik, JavaScript melakukan:

1. Intercept click event
2. Fetch URL target sebagai HTML dengan header `X-Requested-With: XMLHttpRequest`
3. Extract elemen `#page-view` dari response
4. Replace `#page-view` yang ada di halaman saat ini
5. Update `document.title` dan `history.pushState()`
6. Scroll ke atas
7. Dispatch event `davingm:navigated`

### Event listener setelah navigasi

```js
window.addEventListener('davingm:navigated', ({ detail }) => {
    console.log('Navigated to:', detail.url);
    // Re-init components, analytics tracking, dll
});
```

### Fallback otomatis

Jika fetch gagal atau elemen `#page-view` tidak ditemukan di response, browser otomatis fallback ke navigasi normal (`window.location.assign(url)`).

### Batasan

- Hanya untuk link GET. Form POST/PUT/DELETE tetap menggunakan submit biasa agar CSRF, redirect, dan validasi Laravel tetap aman.
- Link dengan `target="_blank"` tidak terpengaruh.
- Modifier key (Ctrl, Meta, Shift, Alt) menonaktifkan soft nav dan membuka link normal.
- JavaScript `window.__DAVINGM__.navigate(url)` dapat dipanggil secara programmatic.

---

## Layout and Components

### layouts/app.blade.php

Layout utama yang dipakai semua halaman (`src/layouts/app.blade.php`):

```html
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO Resolution: seo section > title section + pageMeta --}}
    @hasSection('seo')
        @yield('seo')
    @else
        @hasSection('title')
            <title>@yield('title') | {{ config('app.name') }}</title>
        @endif
        @pageMeta
    @endif
    @stack('head')

    @vite(['src/assets/css/app.css', 'src/assets/js/app.js'])
</head>
<body>
    <x-site-header />
    <main id="page-view" data-page="{{ $frontendPage ?? '' }}">
        @yield('content')
    </main>
    @payload
</body>
</html>
```

**Elemen kunci:**

| Elemen | Fungsi |
|---|---|
| `#page-view` | Target swap untuk soft navigation |
| `data-page` | Menyimpan page key aktif (diisi oleh `Frontend::render()`) |
| `@payload` | Injek JSON state ke HTML sebelum closing body |
| `@stack('head')` | Slot untuk inject elemen head per-halaman |

### x-site-header

Komponen header global (`src/components/site-header.blade.php`). Menampilkan brand logo dan navigasi utama. Bisa di-customize atau diganti sesuai kebutuhan project.

---

## Cache and Runtime Files

Semua file runtime disimpan di `.laravel/cache/` dan **di-ignore Git**:

```
.laravel/
├── cache/
│   ├── manifest.json           <- Daftar semua halaman terdaftar
│   └── payloads/
│       ├── home.json           <- Payload terakhir halaman home
│       ├── barang.index.json   <- Payload terakhir halaman barang/index
│       └── ...
├── .preview                    <- Flag file preview mode (sementara, auto-delete)
├── cli.js                      <- Node.js CLI entry point
├── setup.js                    <- Setup script (dijalankan saat composer install)
└── package.json                <- CLI package config
```

File payload di-write setiap kali halaman dikunjungi. File ini berguna untuk debugging state halaman dan bisa dibaca via `Frontend::payload('page-key')`.

---

## Setup and Installation

### Instalasi otomatis via Composer

```bash
composer create-project davingm/laravel nama-proyek
```

Composer menjalankan `setup.js` otomatis setelah install, yang:

1. Mencari path Node.js di sistem
2. Membuat shell script `artisan` di `~/bin/` dan `~/.local/bin/`
3. Membuat script bisa dieksekusi (`chmod +x`)

### Instalasi manual (jika auto-setup gagal)

```bash
cd .laravel
npm install
npm link
```

Setelah `npm link`, perintah `artisan` tersedia secara global.

Atau jalankan langsung tanpa link:

```bash
node .laravel/cli.js dev
node .laravel/cli.js build
node .laravel/cli.js migrate
```

### Requirements

| Dependency | Versi minimum |
|---|---|
| PHP | 8.3 |
| Composer | 2.x |
| Node.js | 20.19.0+ atau 22.12.0+ |
| Laravel | 13.x |

---

## Project Structure

```
davingm-laravel/
├── app/                     # Backend
│   ├── Console/
│   ├── Http/
│   ├── Models/
│   ├── Providers/
│   ├── Support/
│   ├── database/            # Migrations, factories, seeders
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   └── routes/              # Route definitions
│       ├── web.php
│       └── console.php
│
├── src/                     # Frontend
│   ├── index.blade.php      # App entry point (ships in Composer package)
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   ├── components/          # Blade components (user-created)
│   ├── layouts/             # Layouts (user-created)
│   └── pages/               # Auto-routed pages (user-created)
│       ├── index.blade.php
│       ├── about.blade.php
│       └── ...
│
├── config/
├── bootstrap/
├── public/
├── storage/
├── tests/
├── artisan
├── composer.json
└── package.json
```

> `src/pages/`, `src/layouts/`, dan `src/components/` tidak disertakan saat
> publish ke Composer — hanya `src/index.blade.php` yang dikirim. Folder
> `pages/` dibuat otomatis saat user pertama kali menjalankan `php artisan make:page`.

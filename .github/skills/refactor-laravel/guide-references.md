# Panduan Restrukturisasi Struktur Folder Laravel (untuk Starter Framework Custom)

> Disusun berdasarkan dokumentasi resmi Laravel, source code framework, dan sumber terverifikasi lainnya. Panduan ini bisa langsung diberikan ke AI assistant lain sebagai instruksi kerja.

---

## 1. Prinsip Dasar

Laravel 11+ memperkenalkan struktur aplikasi yang sudah "streamlined" — banyak konfigurasi bootstrap lama sudah dipindah ke satu file `bootstrap/app.php`, sehingga kustomisasi path jauh lebih mudah dibanding Laravel 8/9/10.

Semua kustomisasi path dilakukan di **satu tempat**: `bootstrap/app.php`, melalui method-method resmi pada instance `Illuminate\Foundation\Application`.

Referensi:
- Laravel 11.x Release Notes — Streamlined Application Structure: https://laravel.com/docs/11.x/releases
- Source code `Application.php` (definisi method path): https://github.com/laravel/framework/blob/11.x/src/Illuminate/Foundation/Application.php
- Facade `App` — daftar lengkap method path: https://api.laravel.com/docs/12.x/Illuminate/Support/Facades/App.html

---

## 2. Target Struktur Akhir

```
project/
├── app/                 # Business logic (Model, Controller, Service, Provider)
├── src/                 # Frontend (menggantikan resources/)
│   ├── assets/
│   ├── components/
│   └── pages/
├── system/              # Infrastruktur framework
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── storage/
├── public/              # Tetap di root (web root)
├── tests/
├── artisan
├── composer.json
└── package.json
```

Catatan: `public/`, `vendor/`, `artisan` **sebaiknya tetap di lokasi default**. Alasan teknis ada di bagian 6.

---

## 3. Langkah per Folder

### 3.1 `config/` → `system/config/`

**Status:** Didukung resmi via method `useConfigPath()`.

```php
// bootstrap/app.php
$app = Application::configure(basePath: dirname(__DIR__))
    ->create();

$app->useConfigPath($app->basePath('system/config'));
```

Langkah:
1. Pindahkan isi folder `config/*.php` ke `system/config/`.
2. Tambahkan baris `useConfigPath()` di atas.
3. Jalankan `php artisan config:clear` setelah perubahan.

**Peringatan:** package pihak ketiga yang menggunakan helper resmi `config_path()` akan tetap aman. Package yang hardcode string `'config/xxx.php'` bisa bermasalah — jarang terjadi tapi perlu dicek satu per satu kalau memakai package non-mainstream.

---

### 3.2 `database/` → `system/database/`

**Status:** Didukung resmi via `useDatabasePath()`.

```php
$app->useDatabasePath($app->basePath('system/database'));
```

Ini mencakup `migrations/`, `factories/`, `seeders/`. Setelah pindah, jalankan:
```bash
php artisan migrate
```
untuk memastikan Laravel membaca migration dari path baru.

---

### 3.3 `routes/` → `system/routes/`

**Status:** Paling mudah — memang dirancang untuk dikustomisasi lewat `withRouting()`.

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../system/routes/web.php',
        api: __DIR__.'/../system/routes/api.php',
        commands: __DIR__.'/../system/routes/console.php',
        health: '/up',
    )
    ->create();
```

Referensi: Laravel 11.x Release Notes bagian "The Application Bootstrap File" mencontohkan persis pola `withRouting()` ini — https://laravel.com/docs/11.x/releases

---

### 3.4 `storage/` → `system/storage/`

**Status:** Didukung resmi via `useStoragePath()`, tapi butuh perhatian ekstra.

```php
$app->useStoragePath($app->basePath('system/storage'));
```

**Perhatian khusus:**
- `storage/` dipakai internal untuk cache view compiled, session file, log, framework cache — folder ini **harus writable** oleh web server.
- Kalau ditaruh dalam `system/`, pastikan permission (`chmod`/`chown`) hanya berlaku ke `system/storage/`, bukan ke seluruh `system/` (karena `config/` dan `database/` sebaiknya read-only untuk keamanan).

Diskusi teknis komunitas soal override storage path:
https://laracasts.com/discuss/channels/laravel/change-default-path-for-storage-folder

---

### 3.5 `bootstrap/` — TIDAK bisa lewat method resmi

**Status:** Tidak ada method `useBootstrapPath()` yang otomatis mengubah lokasi file entry point. Ini berbeda dari 4 folder di atas.

Alasan teknis: `public/index.php` melakukan `require` langsung dengan path relatif hardcoded:
```php
$app = require_once __DIR__.'/../bootstrap/app.php';
```

Untuk memindahkan `bootstrap/`, kamu harus **edit manual**:
1. `public/index.php` — ubah path require ke lokasi baru.
2. Cek `composer.json` — beberapa setup optimize/cache autoloader mereferensikan `bootstrap/cache/`.

Referensi soal risiko dan cara manualnya (studi kasus package `pathconfig` yang pernah menangani ini untuk Laravel lama):
https://packagist.org/packages/davestewart/pathconfig

**Rekomendasi:** Karena tidak ada API resmi, pertimbangkan **`bootstrap/` tetap di root** kecuali kamu benar-benar butuh root yang sangat minimal. Nilai tambahnya kecil dibanding risiko salah require path yang bikin seluruh aplikasi down.

---

## 4. `resources/` → `src/`

Bukan soal path config, tapi soal pergantian filosofi struktur (mirip pola Nuxt/page-based routing). Langkah:
1. Pindahkan `resources/views` → `src/pages` (Blade view).
2. Pindahkan `resources/css`, `resources/js` → `src/assets/`.
3. Update `vite.config.js` — ubah entry point sesuai lokasi baru.
4. Kalau ada view yang di-load via `view('nama.view')`, tambahkan path baru ke Laravel lewat:
```php
// dalam AppServiceProvider::boot()
View::addLocation(base_path('src/pages'));
```

---

## 5. Yang JANGAN Dipindah

| Folder | Alasan |
|---|---|
| `vendor/` | Dikelola penuh oleh Composer, memindahkan merusak autoload. |
| `public/` | Web root — harus sinkron dengan konfigurasi web server (nginx/Apache). Kalau lupa update virtual host, situs down total. |
| `artisan` | Entry point CLI, di-reference oleh banyak tool eksternal (deployment script, CI/CD) yang mengasumsikan lokasi default. |

---

## 6. Catatan Khusus: Laravel Breeze

Kalau starter kit kamu akan memakai **Laravel Breeze** untuk autentikasi:

- Breeze **bukan** package yang jalan dari `vendor/` saat runtime seperti Fortify/Jetstream — Breeze adalah *stub publisher*. Command `php artisan breeze:install` men-generate file fisik (controller, route, view) satu kali, lalu Breeze tidak dipakai lagi setelahnya.
- Installer Breeze **hardcode** menulis ke `routes/web.php` dan `resources/views/auth/*.blade.php` — dia tidak tahu kalau kamu sudah mengarahkan path custom.

**Urutan yang disarankan:**
1. Install Breeze dulu di struktur Laravel default (`routes/`, `resources/` masih standar).
2. Jalankan `php artisan breeze:install`.
3. Baru pindahkan hasil generate (view & route auth) ke struktur custom kamu secara manual.
4. Update `withRouting()` dan `View::addLocation()` supaya route dan view hasil Breeze ikut terbaca dari lokasi baru.

Referensi resmi Breeze: https://github.com/laravel/breeze

---

## 7. Checklist Eksekusi (untuk AI/diri sendiri)

- [ ] Buat folder `system/{bootstrap,config,database,routes,storage}` (bootstrap opsional, lihat bagian 3.5)
- [ ] Pindahkan isi `config/` → `system/config/`, tambahkan `useConfigPath()`
- [ ] Pindahkan isi `database/` → `system/database/`, tambahkan `useDatabasePath()`
- [ ] Pindahkan isi `routes/` → `system/routes/`, update `withRouting()`
- [ ] Pindahkan isi `storage/` → `system/storage/`, tambahkan `useStoragePath()`, cek permission
- [ ] Hapus `resources/`, buat `src/{assets,components,pages}`, update `vite.config.js`
- [ ] Install Breeze di struktur default dulu, baru migrasikan hasil generate-nya
- [ ] Jalankan `composer dump-autoload`, `php artisan config:clear`, `php artisan route:clear`, `php artisan view:clear`
- [ ] Test full request cycle (halaman utama + login) sebelum lanjut restrukturisasi lain

---

## 8. Sumber Referensi Lengkap

1. Laravel 11.x Release Notes (Streamlined Application Structure) — https://laravel.com/docs/11.x/releases
2. Illuminate\Foundation\Application source code — https://github.com/laravel/framework/blob/11.x/src/Illuminate/Foundation/Application.php
3. Facade App — daftar method path lengkap — https://api.laravel.com/docs/12.x/Illuminate/Support/Facades/App.html
4. Diskusi komunitas: custom storage path — https://laracasts.com/discuss/channels/laravel/change-default-path-for-storage-folder
5. Diskusi komunitas: custom public path — https://laracasts.com/discuss/channels/laravel/laravel-10-change-public-folder-to-public-html-on-shared-server
6. Package `pathconfig` (studi kasus refactor path folder Laravel/Lumen) — https://packagist.org/packages/davestewart/pathconfig
7. Laravel Breeze — repository resmi — https://github.com/laravel/breeze
# 5. Struktur folder

Path pada bab ini relatif terhadap **root aplikasi**, yaitu folder project yang berisi file `artisan`. Folder-folder berikut adalah bagian dari aplikasi yang digunakan developer.

## Backend aplikasi

| Path | Isi dan tanggung jawab |
|---|---|
| `app/Models/` | Model Eloquent untuk data aplikasi. |
| `app/Http/Controllers/` | Controller HTTP untuk menangani request dan menghubungkan proses aplikasi dengan response. |
| `app/Http/Middleware/` | Middleware untuk memproses request/response. |
| `app/Http/Requests/` | Form Request untuk validasi dan otorisasi input bila digunakan. |
| `app/Providers/` | Service provider aplikasi; di sini directive Blade dan path komponen didaftarkan. |
| `app/Support/` | Kode pendukung khusus, termasuk `PageRouter`, `Frontend`, dan helper halaman. |
| `app/Console/Commands/` | Command Artisan aplikasi seperti build dan preview. |
| `app/routes/` | Route web dan command berbasis closure. |
| `app/database/` | Migration, factory, seeder, dan file SQLite sesuai konfigurasi bootstrap. |

## Frontend Blade dan asset

| Path | Isi dan tanggung jawab |
|---|---|
| `src/pages/` | File halaman Blade; file non-partial didaftarkan sebagai route GET otomatis. |
| `src/layouts/` | Layout Blade bersama, termasuk `app.blade.php`. |
| `src/components/` | Komponen Blade anonymous yang dapat dipanggil dengan sintaks komponen Blade. |
| `src/assets/js/` | JavaScript sumber untuk interaksi browser. |
| `src/assets/css/` | CSS sumber dan entry Tailwind CSS. |

## Framework dan tooling

| Path | Isi dan tanggung jawab |
|---|---|
| `.laravel/` | Tooling framework: launcher `artisan`, generator, template generator, dan cache frontend. Developer biasanya memakai command-nya dan tidak perlu mengubah file cache. |
| `bootstrap/` | Inisialisasi Laravel dan pendaftaran konfigurasi aplikasi. |
| `config/` | Pengaturan Laravel, seperti database, view, cache, dan layanan lain. |
| `vite.config.js` | Konfigurasi build CSS dan JavaScript dengan Vite. |

## Runtime, dependency, dan test

| Path | Isi dan tanggung jawab |
|---|---|
| `public/` | Document root web; `public/build/` berisi output Vite. |
| `storage/` | Log dan file runtime yang digunakan Laravel. |
| `tests/Feature/`, `tests/Unit/` | Test perilaku aplikasi dan unit kode. |
| `vendor/` | Dependency PHP yang dipasang Composer. |
| `node_modules/` | Dependency JavaScript yang dipasang npm. |

Folder dependency, cache, dan hasil build bukan tempat untuk mengedit source. Ubah source terkait lalu jalankan generator atau build yang diperlukan.

## Konvensi penting

Di project ini:

- Route web aktif berada di `app/routes/web.php`.
- Migration berada di `app/database/migrations/`.
- Root Blade view berada di `src/`; halaman berada di `src/pages/`.

Path tersebut adalah struktur aplikasi AutoLaravel. Saat membuat fitur, tempatkan file pada folder yang sesuai dan gunakan generator bila tersedia.

## Langkah berikutnya

Pelajari pemetaan file halaman ke URL pada [Bab 6: Routing dan halaman](./06-routing-dan-halaman.md).

---

[Kembali: 4. Konfigurasi](./04-konfigurasi.md) · [Indeks dokumentasi](../README.md) · Lanjut: [6. Routing dan halaman](./06-routing-dan-halaman.md)

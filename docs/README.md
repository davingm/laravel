# Dokumentasi

Dokumentasi dipisahkan berdasarkan kesiapan dan tujuannya:

## `guidness/` — panduan framework

Baca bab dasar secara berurutan. Setiap bab adalah halaman mandiri untuk navigasi sidebar, dengan tautan ke bab sebelumnya dan berikutnya. Jalankan command dari root aplikasi, yaitu folder project yang berisi file `artisan`.

### Panduan utama

1. [Pengenalan](./guidness/01-pengenalan.md)
2. [Instalasi dan persyaratan](./guidness/02-instalasi.md)
3. [Memulai aplikasi](./guidness/03-memulai-aplikasi.md)
4. [Konfigurasi](./guidness/04-konfigurasi.md)
5. [Struktur folder](./guidness/05-struktur-folder.md)
6. [Routing dan halaman](./guidness/06-routing-dan-halaman.md)
7. [Backend: controller, model, dan validasi](./guidness/07-backend.md)
8. [Blade dan frontend](./guidness/08-blade-dan-frontend.md)
9. [Database dan migration](./guidness/09-database-dan-migration.md)
10. [Artisan dan generator](./guidness/10-artisan-dan-generator.md)
11. [Test dan build](./guidness/11-test-dan-build.md)

### Panduan fitur

- [Auto CRUD](./guidness/auto-crud.md) — membuat CRUD dari schema database aktif.
- [Dashboard Layout Generator](./guidness/dashboard-layout-generator.md) — membuat layout sidebar atau navbar.
- [Role Middleware Generator](./guidness/role-middleware-generator.md) — membuat middleware role dan menggunakannya pada route atau CRUD.

## `discussion/`

Rancangan arsitektur, catatan perubahan, serta dokumen yang masih perlu disinkronkan atau diverifikasi. Jangan menganggap dokumen di sini sebagai panduan siap pakai sebelum statusnya diperbarui.

- [Package split releases](./discussion/package-split-releases.md) — alur publikasi masih perlu diselaraskan dengan manifest `auto/*` dan workflow yang memeriksa `davingm/*`.
- [Framework reference](./discussion/reference.md) — referensi lama; nama API dan sejumlah detail perlu dicocokkan lagi dengan implementasi saat ini.
- [CRUD Barang dari Nol](./discussion/crud-barang-dari-nol.md) — tutorial memakai path view lama (`resources/views/pages`), sedangkan implementasi saat ini memakai `src/pages`.
- [Rebranding AutoLaravel](./discussion/rebranding-autolaravel.md) — catatan historis perubahan nama dan namespace.

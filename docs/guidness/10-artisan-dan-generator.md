# 10. Artisan dan generator

Daftar command yang benar-benar tersedia pada instalasi dapat diperiksa menggunakan:

```bash
artisan list
```

Jalankan command dari root aplikasi, yaitu folder yang berisi file `artisan`.

## Launcher `artisan`

`artisan` adalah launcher framework. Command khusus `artisan dev` menjalankan Laravel server, queue worker, dan Vite dev server. Command lain diteruskan ke Artisan Laravel:

```bash
artisan route:list
```

Jika launcher global `artisan` belum tersedia pada PATH, gunakan file launcher dari root aplikasi:

```bash
node .laravel/cli.js route:list
```

## Generator framework

| Command | Fungsi |
|---|---|
| `artisan make:page <path>` | Membuat halaman Blade di `src/pages/`. |
| `artisan make:dashboard --layout=sidebar` | Membuat layout dashboard; tersedia pilihan `sidebar` dan `navbar`. |
| `artisan make:roles admin editor` | Membuat middleware untuk daftar role. |
| `artisan crud:generate Product` | Membuat CRUD dari schema tabel yang sudah ada di database aktif. |
| `artisan make:all Product` | Membuat scaffold beberapa layer resource. |
| `artisan frontend:generate` | Membuat ulang manifest halaman. |
| `artisan build` | Menjalankan tahapan test, asset, cache, dan manifest build. |
| `artisan preview` | Menjalankan aplikasi dalam mode preview lokal. |

Opsi command dapat berubah. Periksa bantuan sebelum menjalankan:

```bash
artisan make:page --help
artisan crud:generate --help
artisan build --help
```

## Hasil generator

Generator membuat source file dari stub pada `.laravel/`. Beberapa generator melewati file yang sudah ada untuk mencegah overwrite. Baca output terminal untuk mengetahui file yang dibuat atau dilewati dan tinjau hasilnya sebelum digunakan.

Jika perlu mengganti file hasil generator, ubah file aplikasi setelah memastikan command tidak akan menimpa atau mengabaikan pembaruan tersebut pada rerun.

## Pilih generator sesuai kebutuhan

- Gunakan `artisan make:page` untuk halaman Blade GET sederhana.
- Gunakan `artisan make:dashboard` untuk membuat layout awal.
- Gunakan `artisan make:roles` hanya bila model user dan skema role sesuai asumsi generator.
- Gunakan `artisan crud:generate` setelah tabel tersedia pada koneksi yang benar.
- Gunakan generator Laravel standar seperti `artisan make:model`, `artisan make:controller`, dan `artisan make:migration` jika memerlukan kontrol lebih rinci.

Panduan opsi dan alur tiap fitur:

- [Auto CRUD](./auto-crud.md)
- [Dashboard Layout Generator](./dashboard-layout-generator.md)
- [Role Middleware Generator](./role-middleware-generator.md)

## Langkah berikutnya

Sebelum build atau rilis, jalankan pemeriksaan pada [Bab 11: Test dan build](./11-test-dan-build.md).

---

[Kembali: 9. Database dan migration](./09-database-dan-migration.md) · [Indeks dokumentasi](../README.md) · Lanjut: [11. Test dan build](./11-test-dan-build.md)

# 11. Test dan build

Jalankan perintah pada bab ini dari root aplikasi, yaitu folder yang berisi file `artisan`.

## Menjalankan test

Jalankan seluruh suite:

```bash
artisan test
```

Konfigurasi `phpunit.xml` memisahkan suite `Feature` dan `Unit`. Database test disetel ke SQLite in-memory, sehingga test tidak memakai database development persisten secara default.

Untuk menjalankan satu file test atau test terpilih, gunakan opsi PHPUnit yang didukung oleh versi Laravel/PHPUnit pada dependency project. Contoh:

```bash
artisan test --filter=PageRouterTest
```

Jika test gagal karena izin tulis, cek izin direktori cache/runtime yang digunakan aplikasi. Jangan memperbaiki masalah dengan menghapus folder luas atau mengubah database penting.

## Build frontend saja

Untuk membangun CSS dan JavaScript:

```bash
npm run build
```

Asset hasil build tersimpan di `public/build/`. Jangan mengedit output build secara manual; perbaiki source di `src/assets/` lalu build ulang.

## Pipeline `build`

Command project:

```bash
artisan build
```

Secara default pipeline:

1. Menjalankan suite test.
2. Menjalankan `npm run build`.
3. Membersihkan cache lama.
4. Membuat cache konfigurasi, route, view, dan event.
5. Membuat manifest frontend.

Opsi `--skip-tests` dan `--skip-npm` dapat melewati tahapan masing-masing. Gunakan hanya bila memang diperlukan dan pahami konsekuensinya; opsi tersebut bukan pengganti verifikasi.

## Preview lokal

Preview menjalankan server lokal dengan environment production dan debug nonaktif tanpa mengubah file `.env`:

```bash
artisan preview
artisan preview --port=9000
```

Untuk meminta preview menjalankan build terlebih dahulu:

```bash
artisan preview --build
```

Preview ditujukan untuk pemeriksaan lokal, bukan pengganti konfigurasi deployment production.

## Checklist sebelum menyerahkan perubahan

- Test terkait perubahan berhasil.
- Build asset berhasil bila source CSS/JavaScript atau template Tailwind berubah.
- Route dan hasil generator sudah diperiksa.
- Tidak ada secret, cache runtime, atau `node_modules` yang ditambahkan sebagai source.
- Perubahan database disertai migration yang ditinjau.

## Selesai membaca

Untuk rancangan dan dokumen yang belum disinkronkan, gunakan bagian `discussion/` pada [indeks dokumentasi](../README.md). Untuk panduan fitur, kembali ke [indeks dokumentasi](../README.md).

---

[Kembali: 10. Artisan dan generator](./10-artisan-dan-generator.md) · [Indeks dokumentasi](../README.md)

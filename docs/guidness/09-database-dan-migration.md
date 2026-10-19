# 9. Database dan migration

Database aktif dipilih melalui konfigurasi `DB_CONNECTION` pada `.env`. Migration dan file database aplikasi berada di bawah `app/database/`.

## Pilih koneksi development

Nilai awal `.env.example` menggunakan SQLite. Sebelum menjalankan migration, periksa koneksi aktif dan pastikan itu database development atau test yang benar.

Jangan menjalankan setup atau migration terhadap database production. Jangan gunakan perintah destruktif seperti `migrate:fresh` pada database yang datanya ingin dipertahankan.

## Membuat dan menjalankan migration

Buat migration melalui Artisan:

```bash
artisan make:migration products
```

Command `make:migration` pada framework ini menerima nama tabel dan membentuk migration `create_<nama_tabel>_table`. Berikan nama tabel yang diinginkan, misalnya `products`. Edit file yang dibuat untuk mendefinisikan kolom, index, dan foreign key. Setelah meninjau migration dan koneksi database aktif, jalankan:

```bash
artisan migrate
```

Untuk memeriksa status migration:

```bash
artisan migrate:status
```

Migration adalah source perubahan schema yang dapat ditinjau dan disimpan pada version control. Database lokal dan file hasil build bukan pengganti migration.

## Model dan relasi

Buat model di `app/Models/`. Gunakan relasi Eloquent dan foreign key yang sesuai untuk hubungan antar tabel. Untuk mengandalkan pilihan relasi pada generator CRUD, definisikan foreign key constraint di schema; nama kolom berakhiran `_id` saja belum tentu cukup untuk inferensi relasi generator.

Factory berada di `app/database/factories/` dan seeder berada di `app/database/seeders/`. Gunakan keduanya untuk data test atau data awal yang dapat direproduksi.

## Generator CRUD dan database aktif

`crud:generate` membaca metadata schema dari koneksi aktif untuk membuat file CRUD. Ia tidak menjalankan migration. Urutan umumnya:

1. Buat migration untuk schema tabel.
2. Pastikan schema pada migration sudah benar.
3. Jalankan migration ke database development yang dipilih.
4. Jalankan `crud:generate` dari root aplikasi.
5. Generator membuat model resource, controller, route, dan view; tinjau hasil serta lengkapi authorization sesuai kebutuhan.

Detail lengkap ada di [Panduan Auto CRUD](./auto-crud.md).

## Database test

Konfigurasi PHPUnit project memakai SQLite in-memory melalui `phpunit.xml`, sehingga test tidak perlu memakai database development persisten. Jangan mengubah nilai tersebut ke database penting hanya untuk menjalankan test.

## Langkah berikutnya

Pelajari generator dan command lain pada [Bab 10: Artisan dan generator](./10-artisan-dan-generator.md).

---

[Kembali: 8. Blade dan frontend](./08-blade-dan-frontend.md) · [Indeks dokumentasi](../README.md) · Lanjut: [10. Artisan dan generator](./10-artisan-dan-generator.md)

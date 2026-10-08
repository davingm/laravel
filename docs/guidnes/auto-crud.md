# Panduan Auto CRUD

Panduan ini menjelaskan cara membuat CRUD dari schema database menggunakan command `crud:generate` di aplikasi `playground`.

> Jalankan perintah dari folder `playground/`. Generator membaca schema database yang sedang aktif; migration harus sudah dijalankan sebelum command dipakai.

## 1. Siapkan aplikasi dan database

Pastikan dependency aplikasi sudah terpasang dan koneksi database di `.env` mengarah ke database development atau database uji yang benar. Cek konfigurasi database sebelum menjalankan migration maupun generator.

Dari root repository:

```bash
cd playground
```

Jangan memakai database production untuk mencoba generator. Command akan membuat file aplikasi dan mengubah `app/routes/web.php`.

## 2. Buat model dan migration

Pilih nama model singular dalam PascalCase. Contoh berikut membuat model `Product` dan migration untuk tabel `products`:

```bash
php artisan make:model Product --migration
```

Edit migration agar mendefinisikan kolom yang dibutuhkan. Contoh sederhana:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->timestamps();
});
```

Jika perlu dropdown relasi, buat tabel tujuan terlebih dahulu dan definisikan foreign key constraint pada migration `products`. Misalnya, migration `categories` harus berjalan lebih dahulu, lalu migration `products` dapat berisi:

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});

Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->foreignId('category_id')->nullable()->constrained('categories');
    $table->timestamps();
});
```

Pastikan tabel tujuan dibuat sebelum migration `products` dijalankan. Kolom bernama `category_id` tanpa constraint foreign key tidak dianggap sebagai relasi oleh generator.

Jalankan migration:

```bash
php artisan migrate
```

Jangan memakai `migrate:fresh` atau `migrate:refresh` pada database yang berisi data yang ingin dipertahankan; perintah tersebut dapat menghapus dan membangun ulang tabel.

## 3. Jalankan generator

Untuk schema tabel `products`, jalankan:

```bash
php artisan crud:generate Product
```

Generator menurunkan nama tabel dari nama model. Contoh `Product` akan memakai tabel `products`, resource route `products`, dan direktori halaman `src/pages/products`.

Generator membaca kolom dan foreign key dari database aktif. Ia tidak menjalankan migration. Kolom foreign key dengan constraint akan menjadi relationship dan pilihan `<select>`; kolom `_id` tanpa constraint tidak akan dibuatkan relationship.

## 4. Periksa hasil generate

Untuk contoh `Product`, generator membuat file berikut jika file tersebut belum ada:

```text
app/Models/Product.php
app/Http/Controllers/ProductController.php
src/pages/products/index.blade.php
src/pages/products/create.blade.php
src/pages/products/edit.blade.php
```

Generator juga menambahkan `Route::resource('products', ...)` ke `app/routes/web.php` dan mengecualikan `/products` beserta halaman turunannya dari PageRouter.

File yang sudah ada akan dilewati, bukan ditimpa. Periksa pesan command untuk melihat file yang dibuat atau dilewati. Jika file yang dilewati memang perlu diubah, tinjau dan edit file itu sendiri; generator tidak memperbaruinya.

Controller yang dihasilkan menggunakan pagination 15 item pada halaman index. Method `show()` mengarahkan ke halaman edit. Form menggunakan validasi berdasarkan metadata kolom, dan data foreign key dimuat untuk form create/edit.

## 5. Periksa route

Pastikan resource route terdaftar:

```bash
php artisan route:list --path=products
```

Route resource menyediakan endpoint index, create, store, show, edit, update, dan destroy. Halaman `show` diarahkan controller ke halaman edit.

## 6. Jalankan dan coba CRUD

Jalankan server lokal dari folder `playground`:

```bash
php artisan serve
```

Buka alamat lokal yang ditampilkan oleh Artisan, lalu coba alur berikut:

1. Buka `/products` dan pastikan halaman daftar tampil, termasuk empty state jika belum ada data.
2. Pilih **Tambah data**, isi form, lalu simpan. Jika tabel memiliki foreign key constraint yang didukung, pastikan dropdown tersedia.
3. Pastikan data baru muncul di daftar.
4. Pilih **Edit**, ubah salah satu nilai, lalu simpan. Form edit seharusnya menampilkan nilai model saat ini.
5. Pilih **Hapus** dan pastikan data tidak lagi muncul.

Jika aplikasi memiliki asset frontend yang perlu dibangun, ikuti perintah build yang digunakan project sebelum mencoba halaman.

## 7. Jalankan feature test

Feature test generator membuat tabel fixture, menjalankan command, lalu memeriksa hasil file dan route, termasuk textarea, foreign key, kolom `_id` tanpa constraint, nilai create/edit, rule datetime, proteksi file existing, dan rerun.

Jalankan test dari folder `playground` dengan konfigurasi testing yang mengarah ke database terisolasi:

```bash
php artisan test --filter=CrudGenerateCommandTest
```

Test yang lulus memverifikasi perilaku generator dalam lingkungan test, tetapi tetap lakukan uji halaman lokal bila ingin memastikan render dan interaksi browser.

## Catatan

- Gunakan nama model sederhana seperti `Product` atau `BlogPost`.
- Nama kolom `id`, `created_at`, `updated_at`, dan `deleted_at` tidak dibuat sebagai field form.
- Beberapa tipe teks dibuat sebagai `<textarea>`, dan tipe tanggal/waktu dipetakan ke input HTML yang sesuai.
- Foreign key komposit atau metadata foreign key yang tidak lengkap dilewati.
- Generator tidak menimpa model, controller, view, atau route resource yang sudah ada. Tinjau output sebelum melanjutkan pekerjaan pada file hasil generate.

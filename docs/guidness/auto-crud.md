# Auto CRUD dari Migration

Fitur Auto CRUD membuat sebagian besar file CRUD dari schema database. Developer menulis migration untuk tabel dan relasinya, menjalankan migration, lalu menjalankan satu command generator.

> Jalankan semua command dari root aplikasi, yaitu folder yang berisi file `artisan`. Gunakan database development, bukan production.

## Alur singkat

1. Tulis migration untuk tabel yang diperlukan dan definisikan foreign key constraint.
2. Jalankan migration ke database yang dipilih di `.env`.
3. Jalankan `artisan crud:generate NamaModel`.
4. Tinjau file yang dihasilkan, isi konfigurasi bisnis/authorization, lalu uji halaman.

Generator membaca schema database aktif. Ia tidak membuat atau menjalankan migration.

## 1. Siapkan tabel dan migration

Contoh ini membuat kategori dan produk. Command `make:migration` menerima nama tabel. Berikan nama tabel plural yang diinginkan; misalnya `categories` dan `products`:

```bash
artisan make:migration categories
artisan make:migration products
```

Edit migration `categories`:

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

Edit migration `products` dengan foreign key yang nyata:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->foreignId('category_id')
        ->nullable()
        ->constrained('categories');
    $table->timestamps();
});
```

Urutan migration harus memastikan `categories` dibuat sebelum `products`. Migration diurutkan dari timestamp nama file; periksa urutannya sebelum menjalankan.

Foreign key constraint penting. Kolom bernama `category_id` tanpa constraint tidak dikenali sebagai relasi oleh generator.

## 2. Jalankan migration

Periksa `.env` untuk memastikan database aktif adalah database development yang benar, lalu jalankan:

```bash
artisan migrate
```

Generator memeriksa database aktif, bukan sekadar membaca file migration. Jangan memakai `artisan migrate:fresh` atau `artisan migrate:refresh` pada database dengan data yang ingin dipertahankan.

## 3. Generate CRUD

Setelah tabel `products` tersedia di database, jalankan:

```bash
artisan crud:generate Product
```

Nama model singular `Product` digunakan untuk menemukan tabel `products`. Generator akan membaca kolom dan foreign key dari schema tersebut.

Untuk tabel yang namanya tidak mengikuti plural standar dari nama model, pastikan nama model yang diberikan cocok dengan salah satu nama tabel yang dapat dikenali generator.

## 4. File yang dibuat

Jika file belum ada, generator membuat:

```text
app/Models/Product.php
app/Http/Controllers/ProductController.php
src/pages/products/index.blade.php
src/pages/products/create.blade.php
src/pages/products/edit.blade.php
```

Generator juga menambahkan resource route ke `app/routes/web.php`:

```php
Route::resource('products', \App\Http\Controllers\ProductController::class);
```

Prefix resource tersebut beserta subhalamannya dikecualikan dari file-based PageRouter agar tidak bentrok dengan route CRUD.

Anda tidak perlu membuat model `Product`, controller CRUD, route resource, atau tiga view tersebut secara manual.

## 5. Apa yang diinferensikan dari schema?

Generator membaca tabel dan kolom pada database aktif untuk membentuk:

- `$fillable` pada model utama berdasarkan kolom tabel, selain primary key auto-increment dan timestamp umum.
- Validasi field dari nullability, nilai default, dan tipe kolom.
- Input form yang sesuai tipe kolom; misalnya text panjang menjadi `<textarea>`, boolean menjadi checkbox, dan tanggal menjadi input tanggal/waktu.
- Index data dengan pagination 15 item, form create, dan form edit.
- Relasi `belongsTo` serta dropdown pada form untuk foreign key satu kolom yang didukung.
- Pilihan dropdown dari tabel tujuan. Jika tersedia, kolom `name`, `title`, atau `label` dipakai sebagai teks pilihan; jika tidak, kolom foreign key digunakan.

Foreign key komposit atau metadata foreign key yang tidak lengkap dilewati. Tipe database yang tidak dikenali tetap dapat memerlukan penyesuaian manual pada form atau validasi.

### Model tabel relasi

Generator membuat model hanya untuk resource yang diminta, dalam contoh ini `Product`. Ia membuat method relasi yang merujuk ke `App\Models\Category`, tetapi **tidak** membuat model `Category`.

Pada foreign key `category_id`, model utama mendapatkan relasi sejenis ini:

```php
public function category(): BelongsTo
{
    return $this->belongsTo(\App\Models\Category::class, 'category_id', 'id');
}
```

Jika aplikasi menggunakan relasi Eloquent tersebut, buat model relasi secara terpisah:

```bash
artisan make:model Category
```

Migration tabel `categories` tetap ditulis dan dijalankan seperti langkah sebelumnya. Generator CRUD dapat mengambil opsi dropdown berdasarkan foreign key meskipun model relasi belum dibuat, tetapi pemanggilan method Eloquent `category()` memerlukan class `Category`.

## 6. Periksa dan sesuaikan hasil

Periksa route:

```bash
artisan route:list --path=products
```

Route resource menyediakan `index`, `create`, `store`, `show`, `edit`, `update`, dan `destroy`. Method `show()` pada controller hasil generate mengarahkan pengguna ke halaman edit.

Generator melakukan validasi dasar berdasarkan metadata kolom, tetapi developer tetap perlu meninjau:

- Aturan validasi bisnis yang tidak tersimpan di schema, misalnya batas nilai atau kombinasi field.
- Otorisasi: route yang dihasilkan tidak otomatis mendapat autentikasi atau policy.
- `$fillable` dan input yang memang boleh diubah oleh pengguna.
- Label, tampilan, relasi, pagination, serta perilaku delete sesuai aplikasi.

Generator tidak menimpa file yang sudah ada. Jika model, controller, view, atau route telah tersedia, file tersebut dilewati. Generator bukan mekanisme untuk memperbarui file lama.

## 7. Jalankan dan uji CRUD

Jalankan server lokal:

```bash
artisan serve
```

Buka `/products`, lalu coba membuat, mengedit, dan menghapus data. Untuk produk, pastikan dropdown kategori menampilkan data dari tabel `categories`.

Test generator dapat dijalankan dengan:

```bash
artisan test --filter=CrudGenerateCommandTest
```

Test memeriksa schema, file yang dibuat, field relasi, route, perlindungan file existing, dan perilaku rerun. Uji halaman di browser juga diperlukan untuk memverifikasi pengalaman aplikasi.

---

[Panduan framework](./01-pengenalan.md) · [Indeks dokumentasi](../README.md) · [Artisan dan generator](./10-artisan-dan-generator.md)

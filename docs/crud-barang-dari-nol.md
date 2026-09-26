# Tutorial CRUD Barang dari Nol

Tutorial ini menjelaskan cara membuat CRUD `Barang` pada project Laravel ini. Implementasi mengikuti arsitektur yang sudah dipakai project:

- Backend memakai Laravel Resource Controller.
- View berada di `resources/views/pages`.
- Auto-routing memakai `App\Support\PageRouter`.
- Rendering halaman memakai `App\Support\Frontend`.
- Payload frontend disimpan otomatis ke `.davingm/cache`.
- Navigasi antar halaman dapat memakai `data-navigate`.
- View partial diawali `_` agar tidak dianggap sebagai halaman oleh auto-router.
v
CRUD berarti:

| Operasi | Arti | HTTP |
| --- | --- | --- |
| Create | Membuat barang baru | `POST` |
| Read | Melihat daftar/detail barang | `GET` |
| Update | Mengubah barang | `PUT/PATCH` |
| Delete | Menghapus barang | `DELETE` |

---

## 1. Persiapan Project

Buka terminal pada root project:

```bash
cd C:/Users/Hype/Documents/Developer/Package/laravel-davingm
```

Pastikan PHP, Composer, dan Node tersedia:

```bash
php -v
composer -V
node -v
npm -v
```

Install dependency:

```bash
composer install
npm install
```

File penting yang akan dipakai:

```text
app/Models/Barang.php
app/Http/Requests/StoreBarangRequest.php
app/Http/Requests/UpdateBarangRequest.php
app/Http/Controllers/BarangController.php
database/migrations/*_create_barangs_table.php
database/factories/BarangFactory.php
resources/views/pages/barang/
routes/web.php
tests/Feature/BarangCrudTest.php
```

---

## 2. Membuat Skeleton CRUD dengan Artisan

Laravel menyediakan generator agar struktur file konsisten. Perintah yang dipakai project ini:

```bash
php artisan make:model Barang -mfcrR --no-interaction
```

Arti setiap opsi:

| Opsi | Fungsi |
| --- | --- |
| `make:model Barang` | Membuat model `App\Models\Barang` |
| `-m` | Membuat migration tabel barang |
| `-f` | Membuat factory untuk data dummy/test |
| `-c` | Membuat controller |
| `-r` | Membuat resource controller dengan method CRUD Laravel |
| `-R` | Membuat Form Request untuk store dan update |
| `--no-interaction` | Mencegah command berhenti menunggu input terminal |

Hasilnya adalah:

```text
app/Models/Barang.php
app/Http/Controllers/BarangController.php
app/Http/Requests/StoreBarangRequest.php
app/Http/Requests/UpdateBarangRequest.php
database/factories/BarangFactory.php
database/migrations/*_create_barangs_table.php
```

Menggunakan generator lebih baik daripada membuat file manual karena nama class, namespace, dan struktur awal sudah mengikuti Laravel.

---

## 3. Membuat Struktur Database

Migration yang dipakai:

```php
Schema::create('barangs', function (Blueprint $table) {
    $table->id();
    $table->string('nama');
    $table->string('sku', 100)->unique();
    $table->decimal('harga', 12, 2);
    $table->unsignedInteger('stok')->default(0);
    $table->text('deskripsi')->nullable();
    $table->timestamps();
});
```

Penjelasan tiap kolom:

- `$table->id()` membuat primary key auto-increment.
- `nama` menyimpan nama barang.
- `sku` adalah kode unik barang. `unique()` mencegah dua barang memiliki SKU sama.
- `harga` memakai decimal agar nilai uang tidak mengalami masalah pembulatan seperti floating point.
- `stok` memakai unsigned integer karena stok tidak boleh negatif.
- `deskripsi` boleh kosong karena menggunakan `nullable()`.
- `timestamps()` membuat `created_at` dan `updated_at`.

Jalankan migration:

```bash
php artisan migrate
```

Untuk mengulang database lokal dari nol:

```bash
php artisan migrate:fresh
```

Jangan memakai `migrate:fresh` sembarangan di production karena command ini menghapus semua tabel.

Jika migration sudah pernah dijalankan pada environment bersama, jangan mengubah migration lama. Buat migration baru untuk perubahan schema berikutnya.

---

## 4. Membuat Model Barang

Model aktual:

```php
namespace App\Models;

use Database\Factories\BarangFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama', 'sku', 'harga', 'stok', 'deskripsi'])]
class Barang extends Model
{
    /** @use HasFactory<BarangFactory> */
    use HasFactory;
}
```

### `HasFactory`

Trait ini menghubungkan model dengan factory. Dengan begitu kita dapat membuat data test seperti:

```php
Barang::factory()->create();
```

### Attribute `Fillable`

`#[Fillable(...)]` memberi tahu Eloquent field apa saja yang boleh diisi melalui mass assignment:

```php
Barang::create($request->validated());
```

Ini penting untuk keamanan. Tanpa daftar field yang jelas, input request berisiko mengisi kolom yang seharusnya tidak boleh diubah.

Jangan memasukkan field sensitif seperti `is_admin`, `user_id`, atau kolom internal ke daftar fillable kecuali memang diperlukan.

---

## 5. Membuat Factory

Factory aktual:

```php
public function definition(): array
{
    return [
        'nama' => fake()->words(3, true),
        'sku' => fake()->unique()->bothify('BRG-####'),
        'harga' => fake()->randomFloat(2, 1000, 10000000),
        'stok' => fake()->numberBetween(0, 100),
        'deskripsi' => fake()->optional()->sentence(),
    ];
}
```

Fungsi penting:

- `fake()->words(3, true)` membuat nama barang acak.
- `unique()` memastikan SKU factory tidak berulang dalam satu proses.
- `bothify('BRG-####')` membuat format seperti `BRG-1234`.
- `randomFloat(2, ...)` membuat harga dengan dua angka desimal.
- `numberBetween(0, 100)` membuat stok integer.
- `optional()` membuat deskripsi kadang-kadang bernilai `null`.

Factory dipakai untuk test, seeder, dan data development. Factory tidak dipakai untuk input user asli.

---

## 6. Validasi dengan Form Request

Project ini memakai dua Form Request:

```text
app/Http/Requests/StoreBarangRequest.php
app/Http/Requests/UpdateBarangRequest.php
```

### Store Request

```php
public function authorize(): bool
{
    return true;
}

public function rules(): array
{
    return [
        'nama' => ['required', 'string', 'max:255'],
        'sku' => ['required', 'string', 'max:100', 'unique:barangs,sku'],
        'harga' => ['required', 'numeric', 'min:0'],
        'stok' => ['required', 'integer', 'min:0'],
        'deskripsi' => ['nullable', 'string', 'max:2000'],
    ];
}
```

Penjelasan rule:

- `required`: field harus dikirim.
- `string`: nilai harus berupa teks.
- `max:255`: membatasi panjang nama.
- `unique:barangs,sku`: SKU belum boleh ada di tabel `barangs`.
- `numeric`: harga boleh berupa angka integer atau decimal.
- `integer`: stok wajib bilangan bulat.
- `min:0`: harga dan stok tidak boleh negatif.
- `nullable`: deskripsi boleh kosong.

### Update Request

Update hampir sama, tetapi rule SKU berbeda:

```php
'sku' => [
    'required',
    'string',
    'max:100',
    Rule::unique('barangs', 'sku')->ignore($this->route('barang')),
],
```

Saat edit, SKU milik barang yang sedang diedit harus diabaikan. Kalau tidak diabaikan, menyimpan barang tanpa mengubah SKU akan dianggap sebagai duplikasi dirinya sendiri.

`$this->route('barang')` mengambil parameter route hasil implicit model binding.

### `authorize()`

Saat ini nilainya `true`, jadi semua user boleh mengakses CRUD. Pada aplikasi dengan login, bagian ini sebaiknya diganti dengan pemeriksaan permission atau policy.

Validation dan authorization berbeda:

- Validation menjawab: apakah data input valid?
- Authorization menjawab: apakah user boleh melakukan operasi ini?

---

## 7. Resource Controller

Route resource Laravel memakai tujuh method standar:

| Method | Tujuan |
| --- | --- |
| `index()` | Menampilkan daftar |
| `create()` | Menampilkan form tambah |
| `store()` | Menyimpan data baru |
| `show()` | Menampilkan detail |
| `edit()` | Menampilkan form edit |
| `update()` | Menyimpan perubahan |
| `destroy()` | Menghapus data |

### `index()`

```php
public function index(): View
{
    return Frontend::render('barang.index', 'barang.index', [
        'title' => 'Barang | '.config('app.name', 'Laravel'),
        'description' => 'Kelola daftar barang.',
        'barangs' => Barang::query()->latest()->paginate(10),
    ]);
}
```

`Barang::query()` memulai query Eloquent.

`latest()` mengurutkan berdasarkan `created_at` terbaru.

`paginate(10)` membatasi 10 barang per halaman. Ini lebih aman daripada mengambil semua data sekaligus ketika tabel sudah besar.

`Frontend::render()` menggantikan `view()` biasa dan menambahkan payload frontend. Parameter-nya:

```php
Frontend::render(
    'barang.index', // view key tanpa prefix pages
    'barang.index', // page key untuk cache/payload
    [               // data untuk Blade
        'title' => '...',
        'barangs' => $barangs,
    ],
);
```

### `create()`

```php
public function create(): View
{
    return Frontend::render('barang.create', 'barang.create', [
        'title' => 'Tambah Barang | '.config('app.name', 'Laravel'),
    ]);
}
```

Method ini hanya menampilkan form. Belum ada data database yang disimpan.

### `store()`

```php
public function store(StoreBarangRequest $request): RedirectResponse
{
    $barang = Barang::create($request->validated());

    return redirect()
        ->route('barang.show', $barang)
        ->with('success', 'Barang berhasil ditambahkan.');
}
```

Alur method:

1. Laravel menjalankan `StoreBarangRequest` sebelum controller.
2. Jika validasi gagal, user dikembalikan ke form dengan error.
3. `$request->validated()` hanya mengambil field yang lolos validasi.
4. `Barang::create()` menyimpan data menggunakan field yang diizinkan `#[Fillable]`.
5. User diarahkan ke halaman detail barang.
6. `with('success', ...)` mengirim flash message satu kali ke session.

Jangan menggunakan `$request->all()` untuk menyimpan data karena bisa memasukkan field yang tidak dimaksudkan.

### `show()`

```php
public function show(Barang $barang): View
{
    return Frontend::render('barang.show', 'barang.show', [
        'title' => $barang->nama.' | Barang',
        'barang' => $barang,
    ]);
}
```

`Barang $barang` memakai implicit route model binding. Jika URL berisi `/barang/5`, Laravel otomatis mencari `Barang` dengan ID `5`. Jika tidak ditemukan, Laravel mengembalikan 404.

### `edit()`

```php
public function edit(Barang $barang): View
{
    return Frontend::render('barang.edit', 'barang.edit', [
        'title' => 'Edit '.$barang->nama.' | Barang',
        'barang' => $barang,
    ]);
}
```

Method ini mengirim object barang ke form agar input dapat diisi nilai lama.

### `update()`

```php
public function update(UpdateBarangRequest $request, Barang $barang): RedirectResponse
{
    $barang->update($request->validated());

    return redirect()
        ->route('barang.show', $barang)
        ->with('success', 'Barang berhasil diperbarui.');
}
```

Perbedaannya dengan `store()` adalah update memakai object yang sudah ditemukan melalui route model binding.

### `destroy()`

```php
public function destroy(Barang $barang): RedirectResponse
{
    $barang->delete();

    return redirect()
        ->route('barang.index')
        ->with('success', 'Barang berhasil dihapus.');
}
```

`delete()` menghapus row dari database. Untuk data penting, pertimbangkan Soft Deletes agar data tidak benar-benar hilang.

---

## 8. Route Resource

Di `routes/web.php`:

```php
Route::resource('barang', BarangController::class);
```

Satu baris ini menghasilkan route berikut:

```text
GET        /barang                 barang.index
POST       /barang                 barang.store
GET        /barang/create          barang.create
GET        /barang/{barang}        barang.show
PUT/PATCH  /barang/{barang}        barang.update
DELETE     /barang/{barang}        barang.destroy
GET        /barang/{barang}/edit   barang.edit
```

Nama route dipakai di Blade dengan:

```blade
route('barang.index')
route('barang.create')
route('barang.store')
route('barang.show', $barang)
route('barang.edit', $barang)
route('barang.update', $barang)
route('barang.destroy', $barang)
```

Lebih baik memakai nama route daripada menulis URL manual karena URL dapat berubah tanpa harus mencari dan mengubah semua template.

---

## 9. Auto-Routing Pages Custom

Project ini memiliki `App\Support\PageRouter` yang memindai folder:

```text
resources/views/pages
```

Contoh konvensi:

```text
pages/home.blade.php          -> GET /home
pages/about/index.blade.php   -> GET /about
pages/blog/[slug].blade.php   -> GET /blog/{slug}
```

Untuk CRUD barang, route resource membutuhkan data dari controller. Karena itu namespace barang dikecualikan:

```php
PageRouter::register([
    'middleware' => ['web'],
    'exclude' => ['barang'],
]);
```

Artinya:

- Auto-router tetap aktif untuk halaman lain.
- File `pages/barang` tetap menjadi sumber view CRUD.
- Route `/barang` tetap dikendalikan oleh `BarangController`.
- Tidak terjadi konflik route antara halaman otomatis dan resource controller.

File partial diawali underscore:

```text
resources/views/pages/barang/_form.blade.php
```

`PageRouter` dan generator manifest sengaja melewati file yang namanya diawali `_`. Jadi `_form.blade.php` hanya bisa di-include, bukan dianggap halaman atau route.

---

## 10. Layout, Payload, dan Soft Navigation

`Frontend::render()` membuat payload seperti:

```json
{
    "page": "barang.index",
    "url": "http://localhost:8000/barang",
    "path": "barang",
    "data": {
        "title": "Barang | Laravel"
    },
    "meta": {
        "title": "Barang | Laravel",
        "description": "Kelola daftar barang."
    },
    "generated_at": "2026-09-26T00:00:00+00:00"
}
```

Payload ditulis ke:

```text
.davingm/cache/payloads/barang.index.json
```

Folder `.davingm/cache` di-ignore Git. Cache tersebut adalah hasil generate lokal, bukan source code yang perlu dibawa ke repository.

Layout `resources/views/layouts/app.blade.php` memakai:

```blade
@pageMeta
@vite(['resources/css/app.css', 'resources/js/app.js'])
...
@payload
```

`@pageMeta` menulis `<title>` dan description dari payload.

`@payload` menulis JSON state ke HTML:

```html
<script type="application/json" data-page-payload>...</script>
```

Link custom dapat memakai:

```blade
<a
    href="{{ route('barang.create') }}"
    data-navigate="{{ route('barang.create') }}"
>
    Tambah barang
</a>
```

JavaScript membaca `data-navigate`, mengambil HTML dengan `fetch()`, lalu mengganti element `#page-view`. Jika fetch gagal, browser kembali memakai navigasi normal.

Untuk form `POST`, `PUT`, dan `DELETE`, browser tetap memakai submit form biasa agar CSRF, redirect, dan error validation Laravel tetap bekerja dengan aman.

---

## 11. Membuat Partial Form

Create dan edit memiliki field yang sama, sehingga field disimpan satu kali di:

```text
resources/views/pages/barang/_form.blade.php
```

Dipakai dari create:

```blade
@include('pages.barang._form', [
    'submitLabel' => 'Simpan barang',
])
```

Dipakai dari edit:

```blade
@include('pages.barang._form', [
    'submitLabel' => 'Simpan perubahan',
])
```

Partial memakai:

```blade
@csrf
```

Token ini wajib untuk request yang mengubah database. Laravel memeriksa token agar request tidak berasal dari website lain.

Form edit memakai:

```blade
@method('PUT')
```

HTML form hanya mendukung `GET` dan `POST`. Blade method spoofing mengubah POST menjadi PUT ketika request dikirim ke Laravel.

Nilai input memakai:

```blade
value="{{ old('nama', $barang->nama ?? '') }}"
```

Prioritasnya:

1. `old('nama')` jika validasi sebelumnya gagal.
2. `$barang->nama` ketika edit.
3. String kosong ketika create.

Dengan begitu user tidak kehilangan input saat validasi gagal.

Error field ditampilkan dengan:

```blade
@error('nama')
    <small>{{ $message }}</small>
@enderror
```

---

## 12. Testing CRUD

Test berada di:

```text
tests/Feature/BarangCrudTest.php
```

Test memakai `RefreshDatabase`:

```php
use RefreshDatabase;
```

Trait ini membuat database test bersih untuk setiap test. Test tidak merusak database development utama.

Contoh membuat data:

```php
Barang::factory()->create([
    'nama' => 'Kursi kerja',
]);
```

Test yang tersedia:

1. Halaman index bisa dibuka dan menampilkan barang.
2. Barang bisa dibuat melalui POST.
3. Input kosong ditolak oleh validasi.
4. Barang bisa di-update lalu dihapus.

Jalankan test khusus CRUD:

```bash
php artisan test tests/Feature/BarangCrudTest.php --compact
```

Jalankan semua test:

```bash
php artisan test --compact
```

Test feature lebih cocok daripada test unit untuk CRUD karena yang diuji adalah perilaku HTTP nyata: route, validation, controller, database, redirect, dan view.

---

## 13. Menjalankan Aplikasi

Generate manifest frontend:

```bash
artisan frontend:generate
```

Build asset production:

```bash
npm run build
```

Jalankan mode development:

```bash
artisan dev
```

Perintah custom `artisan dev` menjalankan:

- Laravel server.
- Queue worker.
- Vite dev server.
- Generator manifest frontend.

Jika ingin menjalankan server Laravel saja:

```bash
php artisan serve
```

---

## 14. Checklist Jika CRUD Error

### Tabel belum ada

Jalankan:

```bash
php artisan migrate
```

### Route tidak ditemukan

Periksa:

```bash
php artisan route:list --path=barang
```

Harus ada 7 route resource `barang.*`.

### View tidak ditemukan

Pastikan file berada di:

```text
resources/views/pages/barang/index.blade.php
resources/views/pages/barang/create.blade.php
resources/views/pages/barang/show.blade.php
resources/views/pages/barang/edit.blade.php
resources/views/pages/barang/_form.blade.php
```

Dan controller memakai:

```php
Frontend::render('barang.index', 'barang.index', [...]);
```

### Partial form tidak ditemukan

Include harus memakai namespace lengkap:

```blade
@include('pages.barang._form')
```

Bukan:

```blade
@include('barang._form')
```

### Route auto-router bentrok

Pastikan `routes/web.php` memiliki:

```php
Route::resource('barang', BarangController::class);

PageRouter::register([
    'middleware' => ['web'],
    'exclude' => ['barang'],
]);
```

Lalu bersihkan cache:

```bash
php artisan route:clear
php artisan view:clear
```

### Asset tidak berubah

Jalankan:

```bash
npm run build
```

Saat development, gunakan:

```bash
artisan dev
```

---

## 15. Urutan Singkat Implementasi

Jika membuat resource lain dengan pola yang sama, urutannya:

1. Buat model, migration, factory, controller, dan requests.
2. Isi migration dan jalankan `php artisan migrate`.
3. Isi model dengan `#[Fillable(...)]`.
4. Isi factory untuk data test.
5. Isi Store dan Update Form Request.
6. Isi tujuh method resource controller.
7. Tambahkan `Route::resource()`.
8. Buat view di `resources/views/pages/nama-resource`.
9. Buat partial `_form.blade.php` di folder pages yang sama.
10. Gunakan `Frontend::render()` dari controller.
11. Tambahkan `data-navigate` pada link yang ingin soft navigation.
12. Tambahkan test feature untuk index, create, validation, update, dan delete.
13. Jalankan Pint, test, view cache, dan Vite build.

Dengan pola ini, CRUD tetap mengikuti Laravel, tetapi halaman tetap mengikuti custom frontend framework project ini.

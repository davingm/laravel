# 7. Backend: controller, model, dan validasi

Backend aplikasi menggunakan Laravel. AutoLaravel menambahkan lokasi folder dan generator, tetapi pola dasarnya tetap request → route → middleware → controller → model/service → response.

## Route

Route web manual ditulis di `app/routes/web.php`. Route sederhana dapat mengembalikan view, tetapi proses bisnis sebaiknya ditempatkan pada controller agar route tetap mudah dibaca.

Resource route Laravel dapat menghubungkan operasi CRUD ke controller:

```php
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::resource('products', ProductController::class);
```

Tambahkan autentikasi atau middleware lain sesuai kebutuhan aplikasi dan authorization policy. Jangan menganggap route otomatis halaman sudah memberikan perlindungan akses.

## Controller

Controller berada di `app/Http/Controllers/`. Controller menerima request, mengorkestrasi pekerjaan backend, dan mengembalikan view, redirect, atau response.

Contoh tanggung jawab controller:

- Memvalidasi input melalui Form Request atau `$request->validate(...)`.
- Memanggil model/service untuk membaca atau mengubah data.
- Mengirim data yang diperlukan ke view.
- Mengembalikan redirect setelah operasi tulis.

Hindari menaruh query dan business logic besar di Blade. Untuk kode yang dipakai lintas beberapa controller, pertimbangkan service atau class dukungan yang sesuai.

## Model Eloquent

Model berada di `app/Models/` dan biasanya merepresentasikan tabel database. Contoh model bernama `Product` secara konvensi terhubung ke tabel `products`.

Model digunakan untuk query Eloquent, relasi, cast, dan aturan mass assignment. Tetapkan field yang dapat diisi dengan aman menurut struktur Laravel yang dipakai project; jangan menerima input pengguna tanpa validasi dan perlindungan mass assignment yang tepat.

## Form Request dan validasi

Form Request dapat diletakkan di `app/Http/Requests/`. Gunakan untuk memisahkan aturan validasi dan, bila perlu, pemeriksaan authorization dari controller.

Validasi memastikan bentuk input sesuai kebutuhan, tetapi tidak menggantikan pemeriksaan authorization. Aplikasi tetap harus memastikan bahwa pengguna berhak mengakses atau mengubah record terkait.

## Middleware dan authorization

Middleware berada di `app/Http/Middleware/` dan dapat membatasi atau mengubah alur request. Middleware role generator menghasilkan pemeriksaan role dasar dari atribut `user->role`; model dan skema user harus cocok dengan asumsi itu.

Untuk aturan akses per-record atau kebijakan yang kompleks, gunakan mekanisme authorization Laravel seperti policy/gate. Jangan menganggap generator role adalah sistem permission lengkap.

## Render view dengan data

Controller atau route manual dapat mengembalikan halaman dengan helper:

```php
return page('products.index', [
    'title' => 'Produk',
    'products' => Product::query()->latest()->paginate(15),
]);
```

Nama view mengacu pada file di `src/pages/`. Data dikirim ke Blade dan sebagian metadata dibaca oleh sistem payload.

## Langkah berikutnya

Untuk menampilkan halaman serta mengelola asset browser, baca [Bab 8: Blade dan frontend](./08-blade-dan-frontend.md).

---

[Kembali: 6. Routing dan halaman](./06-routing-dan-halaman.md) · [Indeks dokumentasi](../README.md) · Lanjut: [8. Blade dan frontend](./08-blade-dan-frontend.md)

# 6. Routing dan halaman

Framework menyediakan auto-routing untuk halaman Blade sederhana dan tetap mendukung route Laravel biasa untuk proses aplikasi.

## File-based routing

`PageRouter` memindai `src/pages/` saat route web didaftarkan. File `.blade.php` yang tidak diawali `_` dibuatkan route GET.

| File | URL | Nama route |
|---|---|---|
| `src/pages/index.blade.php` | `/` | `pages` |
| `src/pages/about.blade.php` | `/about` | `pages.about` |
| `src/pages/about/index.blade.php` | `/about` | `pages.about` |
| `src/pages/about/team.blade.php` | `/about/team` | `pages.about.team` |
| `src/pages/blog/[slug].blade.php` | `/blog/{slug}` | `pages.blog.slug` |

Folder `index` mewakili root folder. Segmen dinamis ditulis menggunakan bracket, misalnya `[id]` atau `[slug]`.

### Partial

File yang nama dasarnya dimulai dengan underscore tidak dibuatkan route. Contoh `src/pages/products/_form.blade.php` dapat dipakai sebagai partial Blade.

### Cegah konflik route

Jika suatu resource sudah memiliki route Laravel sendiri, halaman auto-route dapat dikecualikan melalui `exclude` pada `PageRouter::register()` di `app/routes/web.php`. Pola exact mengecualikan halaman tersebut; pola wildcard seperti `products/*` mengecualikan halaman beserta subhalamannya.

## Menambahkan halaman

Cara langsung adalah membuat file, misalnya `src/pages/contact.blade.php`, lalu cek route:

```bash
artisan route:list --path=contact
```

Generator `make:page` juga tersedia:

```bash
artisan make:page contact
```

Command akan membuat file halaman. Opsi dan perilaku saat file sudah ada dibahas pada [Bab 10: Artisan dan generator](./10-artisan-dan-generator.md).

## Route manual

Gunakan `app/routes/web.php` untuk route yang membutuhkan middleware, parameter, controller, atau data khusus. Contoh route halaman yang memberi data ke view:

```php
use Illuminate\Support\Facades\Route;

Route::get('/about', function () {
    return page('about', [
        'title' => 'Tentang Kami',
        'description' => 'Informasi tentang aplikasi.',
    ]);
})->name('about');
```

Helper `page()` memanggil sistem render framework dan mencari view di bawah `src/pages/`.

Untuk aksi yang mengubah data, gunakan route HTTP yang sesuai dan controller/Form Request, bukan auto-route halaman GET.

## Verifikasi route

Lihat semua route atau filter berdasarkan path:

```bash
artisan route:list
artisan route:list --path=about
```

Jika menambah halaman saat route cache aktif, bersihkan cache route sebelum memeriksa perubahan:

```bash
artisan route:clear
```

## Langkah berikutnya

Untuk memproses request dan data, lanjut ke [Bab 7: Backend](./07-backend.md).

---

[Kembali: 5. Struktur folder](./05-struktur-folder.md) · [Indeks dokumentasi](../README.md) · Lanjut: [7. Backend](./07-backend.md)

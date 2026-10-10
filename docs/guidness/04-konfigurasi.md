# 4. Konfigurasi

Konfigurasi lingkungan aplikasi disimpan di `.env`. File ini berisi nilai lokal atau rahasia dan tidak boleh dimasukkan ke Git. Gunakan `.env.example` sebagai referensi nama variabel yang tersedia.

## Konfigurasi aplikasi

| Variabel | Fungsi |
|---|---|
| `APP_NAME` | Nama aplikasi yang digunakan dalam konfigurasi Laravel. |
| `APP_ENV` | Environment aplikasi, misalnya `local` atau `production`. |
| `APP_KEY` | Kunci enkripsi Laravel; buat dengan `artisan key:generate`. |
| `APP_DEBUG` | Mengatur detail error; aktifkan untuk development lokal, nonaktifkan pada production. |
| `APP_URL` | URL dasar aplikasi. |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | Locale aplikasi dan locale fallback. |

Jangan membagikan `APP_KEY`, password database, token, atau secret lain.

## Database

Koneksi default berasal dari `DB_CONNECTION`. File `.env.example` memilih SQLite:

```dotenv
DB_CONNECTION=sqlite
```

Untuk koneksi berbasis server, atur driver dan kredensial yang sesuai, seperti `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`. Driver dan opsi yang didukung ditentukan oleh `config/database.php`.

Sebelum menjalankan migration atau generator yang membaca schema, pastikan koneksi aktif adalah database development yang benar.

## Session, queue, cache, dan mail

`.env.example` juga memuat variabel seperti `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE`, dan `MAIL_MAILER`. Nilai tersebut menentukan backend Laravel untuk session, job queue, cache, dan pengiriman email.

Pilih driver sesuai layanan yang benar-benar sudah disiapkan. Contohnya, driver database memerlukan tabel terkait; driver Redis memerlukan layanan Redis dan konfigurasi yang sesuai. Jangan menganggap nilai contoh sudah menyediakan layanan tersebut secara otomatis.

## Konfigurasi view khusus

Aplikasi mengubah beberapa lokasi default Laravel:

- `config/view.php` memakai `src/` sebagai root view.
- Blade pages, layouts, dan components berada di bawah `src/`.
- `bootstrap/app.php` mengatur `app/routes/` sebagai file route web.
- Bootstrap mengatur `app/database/` sebagai lokasi database Laravel.
- Framework menyimpan manifest dan payload frontend di `.laravel/cache/`.

Jangan mengubah path ini tanpa memperbarui bootstrap, konfigurasi terkait, generator, test, dan dokumentasi secara konsisten.

## Konfigurasi frontend

`VITE_APP_NAME` tersedia pada `.env.example` untuk nilai yang dapat diekspos ke Vite. Hanya nilai yang aman untuk diketahui browser yang boleh memakai prefix `VITE_`; jangan menaruh secret di dalamnya.

Entry CSS dan JavaScript Vite terdaftar di `vite.config.js`. Untuk mengubah entry, pastikan layout utama memuat entry yang sama melalui `@vite`.

## Cache konfigurasi

Setelah membuat cache konfigurasi, perubahan `.env` mungkin tidak langsung terbaca. Saat development, bersihkan cache bila nilai konfigurasi lama masih digunakan:

```bash
artisan config:clear
```

Jangan menyimpan hasil konfigurasi production atau file cache runtime ke source control.

## Langkah berikutnya

Lihat lokasi source aplikasi pada [Bab 5: Struktur folder](./05-struktur-folder.md).

---

[Kembali: 3. Memulai aplikasi](./03-memulai-aplikasi.md) · [Indeks dokumentasi](../README.md) · Lanjut: [5. Struktur folder](./05-struktur-folder.md)

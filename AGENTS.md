# Panduan agent — davingm/laravel

Panduan ini berisi aturan kerja yang berlaku selama mengembangkan repository. Ini bukan changelog, daftar TODO, atau sumber status rilis; hapus atau perbarui aturan ketika tidak lagi sesuai.

## Pahami repository sebelum mengubahnya 

- Baca `docs/README.md`, lalu baca panduan developer/pengguna yang relevan dengan tugas. Dokumentasi di `docs/discussion/` dapat berupa catatan historis; verifikasi setiap klaim terhadap source sebelum menggunakannya.
- Source runtime Composer berada di `packages/laravel/`. Source framework yang disalin ke runtime berada di `packages/laravel/.laravel/`.
- `playground/` adalah aplikasi Laravel untuk menjalankan dan menguji framework. Ubah source aplikasi di sana, bukan di hasil generate.
- `playground/.laravel/`, `vendor/`, `node_modules/`, cache, dan hasil build adalah output lokal/dependency; jangan mengedit atau menambahkan output tersebut sebagai source.
- Sebelum merencanakan perubahan, periksa daftar draft di `references/` dan baca referensi yang relevan. Isinya materi eksternal, bukan spesifikasi atau source of truth; jangan mengubah, memindahkan, atau memasukkannya ke package.
- Root `composer.json` mematerialisasi aplikasi dari `playground/`. Periksa `.materialize-playground.php` dan `.gitattributes` sebelum mengubah perilaku distribusi starter.

## Cara bekerja

- Periksa source, konfigurasi, dan test yang benar-benar terkait sebelum mengusulkan atau membuat perubahan. Cari pola yang sudah dipakai dan pertahankan antarmuka/perilaku yang tidak diminta untuk berubah.
- Jangan menganggap riwayat diskusi, komentar TODO, lockfile, cache registry, atau pesan lama sebagai status saat ini. Verifikasi versi, ketersediaan package, perilaku Composer/npm, dan fitur GitHub dari manifest/source atau dokumentasi resmi terkini.
- Jangan mengarang status rilis, stabilitas, kompatibilitas, atau hasil test. Jika tidak dapat diverifikasi, nyatakan dengan jelas bahwa hal itu belum diverifikasi.
- Terapkan perubahan kecil yang menyelesaikan akar masalah. Jika perilaku berubah, perbarui dokumentasi dan test yang terkait.
- Laporkan error secara jelas; jangan menyamarkan kegagalan sebagai keberhasilan.
- Jangan memasukkan secret, token, kredensial, atau data lokal ke source, log, dokumentasi, maupun commit.
- Jangan mengubah file di luar cakupan tugas. Jangan menjalankan migrasi destruktif terhadap database developer atau folder default; gunakan test SQLite in-memory atau lingkungan terisolasi.
- Minta klarifikasi bila ada keputusan produk/perilaku yang berdampak besar dan tidak dapat ditentukan dari source atau permintaan.

## Verifikasi

Jalankan perintah dari `playground/`, kecuali perintah menyebut lokasi lain:

```bash
<<<<<<< HEAD
artisan about
artisan route:list
artisan test --compact
=======
composer install
php artisan test --compact
npm ci
>>>>>>> 0135ad8ab7292abc5570312413cb66b3edbd9741
npm run build
```

Pilih pemeriksaan terkecil yang mencakup perubahan. Untuk perubahan runtime, pastikan source di `packages/laravel/.laravel/` tersalin dengan benar ke runtime lokal setelah Composer membangunnya. Jangan mengandalkan hasil test lama; laporkan perintah yang benar-benar dijalankan dan hasilnya.

## Dokumentasi dan komunikasi

- Jaga README, panduan pengguna, panduan developer, dan konfigurasi CI tetap sesuai implementasi saat ini.
- Tulis dokumentasi repository mengikuti bahasa dan istilah yang sudah dipakai di file terkait; tanggapi pengguna dalam bahasa Indonesia.
- Hindari menaruh snapshot waktu seperti versi “terbaru”, status publikasi, atau daftar tugas sementara di panduan tetap. Hubungkan ke sumber resmi/paket atau terangkan cara memverifikasi status tersebut.

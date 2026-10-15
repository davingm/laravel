# Package split releases

> **Status: arsip historis.** Jangan gunakan dokumen ini sebagai status rilis atau prosedur. Periksa manifest, tag, registry, dan [panduan rilis runtime](../dev/publish-runtime-package.md) untuk kondisi terkini.

Dokumen ini dipertahankan sebagai catatan historis tentang workflow split package. Package internal di `packages/` tidak otomatis dipublikasikan hanya karena berada di monorepo.

Source runtime dan Composer plugin berada di `packages/laravel`. Package terpisah `davingm/laravel-runtime` memasang `.laravel/` setelah Composer membangun autoloader. Folder hasil generate diabaikan Git dan dibuat ulang melalui instalasi Composer.

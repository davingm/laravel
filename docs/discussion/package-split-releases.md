# Package split releases

> **Status: arsip, tidak aktif.** Workflow split package sudah diganti dengan CI aplikasi melalui Apache.

Dokumen ini dipertahankan sebagai catatan historis. Package `auth` dan package internal lain di `packages/` tidak dipublikasikan otomatis.

Source runtime dan Composer plugin berada di package `packages/laravel`. Package ini harus dirilis sebagai `davingm/laravel-runtime`; pluginnya membuat `.laravel/` setelah Composer membangun autoloader. Folder hasil generate diabaikan Git dan dipulihkan dengan `composer install`.

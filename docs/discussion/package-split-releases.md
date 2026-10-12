# Package split releases

> **Status: arsip, tidak aktif.** Workflow split package sudah diganti dengan CI aplikasi melalui Apache.

Dokumen ini dipertahankan sebagai catatan historis. Package `auth` dan package internal lain di `packages/` tidak dipublikasikan otomatis.

Source runtime framework berada di `packages/laravel/.laravel/`. Source tersebut ikut dalam archive starter. Hook Composer di `playground/scripts/install-framework.php` menyalinnya ke `.laravel/` setelah autoload dibuat; hasil salinan diabaikan Git dan dapat dibuat ulang dengan `composer install`.

# Merilis package split

Gunakan command internal ini dari root monorepo untuk merilis `packages/laravel`, `packages/devtools`, atau keduanya ke repository package masing-masing:

```bash
composer release
```

Command bersifat interaktif. Ia membaca tag langsung dari remote package, menawarkan bump patch/minor/major berdasarkan versi tertinggi, dan menerima versi SemVer stabil secara manual. Versi hanya menjadi tag Git; manifest Composer package tidak menyimpan nomor versi.

## Alur

1. Pilih runtime, devtools, atau keduanya.
2. CLI memastikan `origin` serta remote package mengarah ke repository yang diharapkan, branch aktif `main`, dan `origin/main` merupakan leluhur dari `main` lokal. Commit lokal yang belum di-push boleh diteruskan; branch yang tertinggal atau divergen ditolak.
3. Pilih bump versi untuk setiap package. Versi yang sudah ada atau tidak lebih tinggi dari tag terakhir ditolak.
4. Jika ada perubahan pada folder package terpilih, CLI menampilkan daftar file, meminta pesan commit, lalu hanya men-stage folder yang dipilih. Perubahan staged yang sudah ada menyebabkan proses berhenti.
5. Tinjau rencana dan setujui sebelum push. Source commit didorong ke `origin/main`, lalu tiap package di-split dengan `git subtree split` dan di-push ke branch `main` beserta tag versi baru.
6. Packagist diperbarui oleh webhook GitHub ketika tag baru tiba. Pastikan webhook masing-masing repository package aktif; command tidak meminta atau menyimpan token Packagist.

Sebelum meminta konfirmasi push, CLI memastikan branch remote package dapat di-fast-forward ke hasil split. Jika commit remote berbeda tetapi isi tree-nya sama dengan checkpoint historis pada split, CLI membuat merge commit yang mempertahankan kedua riwayat dan memakai isi package dari monorepo. Jika tidak ada checkpoint yang cocok, command berhenti tanpa push dan meminta developer meninjau serta menyelaraskan source secara manual. Jangan force-push atau menimpa branch package.

Command tidak melakukan force push dan tidak memindahkan tag. Jika push branch package sukses tetapi push tag gagal, branch remote mungkin sudah maju tetapi versi belum terbit; periksa remote sebelum mengulang command. Jika release beberapa package dan salah satunya gagal, package yang sudah sukses tidak otomatis dibatalkan.

Jika konfirmasi commit disetujui tetapi konfirmasi push terakhir ditolak, commit source tetap ada secara lokal dan belum dikirim. Jalankan command lagi setelah siap melanjutkan, atau tinjau commit tersebut secara manual.

## Persyaratan dan batas aman

- Jalankan dari Git Bash atau shell yang memiliki PHP, Git, dan `git subtree` pada `PATH`.
- Branch lokal harus `main`; `origin/main` boleh tertinggal jika menjadi leluhur commit lokal.
- Pastikan semua perubahan pada package yang dipilih memang siap untuk di-commit. File di luar folder package terpilih tidak ikut di-stage.
- Jangan merilis tag yang sama; command memeriksa tag remote sebelum push.
- Command tidak menjalankan test suite atau memperbarui changelog secara otomatis. Jalankan pemeriksaan package yang sesuai dan perbarui catatan rilis sebelum release.

Untuk rincian split runtime dan validasi setelah publish, lihat [panduan rilis runtime](./publish-runtime-package.md).

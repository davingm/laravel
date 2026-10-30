<?php

// Define application-specific web routes here. Framework routes are loaded by
// the davingm/laravel-runtime package.

// CRUD Routes for Siswa
Route::resource('siswas', \App\Http\Controllers\SiswaController::class);

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alat extends Model
{
    protected $fillable = [
        'nama_alat',
        'deskripsi',
        'kategori_id',
        'stok',
    ];


    public function kategori(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Kategori::class, 'kategori_id', 'id');
    }

    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }
}
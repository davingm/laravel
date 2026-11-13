<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peminjaman extends Model
{
    protected $table = 'peminjamans';

    protected $fillable = [
        'user_id',
        'catatan',
        'tanggal_kembali',
        'tanggal_dipinjam',
        'status',
        'diminta_kembali_pada',
        'dikembalikan_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kembali' => 'date',
            'tanggal_dipinjam' => 'datetime',
            'diminta_kembali_pada' => 'datetime',
            'dikembalikan_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PeminjamanItem::class);
    }

    public function stokSedangDipakai(): bool
    {
        return in_array($this->status, ['disetujui', 'menunggu_pengembalian'], true);
    }
}

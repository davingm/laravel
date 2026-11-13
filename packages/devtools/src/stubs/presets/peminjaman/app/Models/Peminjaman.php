<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Peminjaman extends Model
{
    protected $table = 'peminjamans';

    protected $fillable = [
        'user_id',
        'alat_id',
        'jumlah',
        'catatan',
        'tanggal_kembali',
        'status',
        'diminta_kembali_pada',
        'dikembalikan_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kembali' => 'date',
            'diminta_kembali_pada' => 'datetime',
            'dikembalikan_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class);
    }

    public function stokSedangDipakai(): bool
    {
        return in_array($this->status, ['disetujui', 'menunggu_pengembalian'], true);
    }
}

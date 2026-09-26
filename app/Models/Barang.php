<?php

namespace App\Models;

use Database\Factories\BarangFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama', 'sku', 'harga', 'stok', 'deskripsi'])]
class Barang extends Model
{
    /** @use HasFactory<BarangFactory> */
    use HasFactory;
}

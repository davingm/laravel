<?php

namespace App\Models;

use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama', 'nis'])]
class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory;
}

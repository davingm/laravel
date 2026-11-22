<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PeminjamanDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Endministrator', 'email' => 'admin@example.com', 'role' => 'administrator'],
            ['name' => 'Perlica', 'email' => 'operator@example.com', 'role' => 'operator'],
            ['name' => 'Nairha', 'email' => 'user@example.com', 'role' => 'user'],
            ['name' => 'Davingm', 'email' => 'davingm@example.com', 'role' => 'user'],
            ['name' => 'Erga', 'email' => 'erga@example.com', 'role' => 'user'],
        ] as $user) {
            $existing = DB::table('users')->where('email', $user['email'])->first();
            $values = [
                'name' => $user['name'],
                'role' => $user['role'],
                'updated_at' => now(),
            ];

            if ($existing === null) {
                $values = $user + [
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } elseif ($existing->email_verified_at === null) {
                $values['email_verified_at'] = now();
            }

            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                $values,
            );
        }

        $perkakas = Kategori::firstOrCreate(
            ['nama_kategori' => 'Perkakas'],
            ['deskripsi' => 'Peralatan kerja dan perbaikan.'],
        );
        $elektronik = Kategori::firstOrCreate(
            ['nama_kategori' => 'Elektronik'],
            ['deskripsi' => 'Peralatan elektronik untuk kegiatan.'],
        );

        Alat::firstOrCreate(
            ['nama_alat' => 'Bor listrik'],
            ['kategori_id' => $perkakas->id, 'deskripsi' => 'Bor listrik untuk pekerjaan umum.', 'stok' => 4],
        );
        Alat::firstOrCreate(
            ['nama_alat' => 'Tang kombinasi'],
            ['kategori_id' => $perkakas->id, 'deskripsi' => 'Tang kombinasi ukuran standar.', 'stok' => 6],
        );
        Alat::firstOrCreate(
            ['nama_alat' => 'Proyektor'],
            ['kategori_id' => $elektronik->id, 'deskripsi' => 'Proyektor untuk presentasi.', 'stok' => 3],
        );
        Alat::firstOrCreate(
            ['nama_alat' => 'Laptop'],
            ['kategori_id' => $elektronik->id, 'deskripsi' => 'Laptop untuk kegiatan kantor.', 'stok' => 5],
        );
    }
}

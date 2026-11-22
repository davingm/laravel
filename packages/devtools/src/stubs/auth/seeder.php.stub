<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthSeeder extends Seeder
{
    /**
     * Seed default auth users.
     *
     * Demo accounts for the administrator, operator, and three users.
     * Credentials are intentionally simple for local development only.
     * Change all passwords before deploying to any shared environment.
     */
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Endministrator',
                'email'    => 'admin@example.com',
                'password' => Hash::make('password'),
                'role'     => 'administrator',
            ],
            [
                'name'     => 'Perlica',
                'email'    => 'operator@example.com',
                'password' => Hash::make('password'),
                'role'     => 'operator',
            ],
            [
                'name'     => 'Nairha',
                'email'    => 'user@example.com',
                'password' => Hash::make('password'),
                'role'     => 'user',
            ],
            [
                'name'     => 'Davingm',
                'email'    => 'davingm@example.com',
                'password' => Hash::make('password'),
                'role'     => 'user',
            ],
            [
                'name'     => 'Erga',
                'email'    => 'erga@example.com',
                'password' => Hash::make('password'),
                'role'     => 'user',
            ],
        ];

        foreach ($users as $data) {
            $existing = DB::table('users')->where('email', $data['email'])->first();
            $values = [
                'name' => $data['name'],
                'role' => $data['role'],
                'updated_at' => now(),
            ];

            if ($existing === null) {
                $values = array_merge($data, [
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($existing->email_verified_at === null) {
                $values['email_verified_at'] = now();
            }

            DB::table('users')->updateOrInsert(
                ['email' => $data['email']],
                $values,
            );
        }
    }
}

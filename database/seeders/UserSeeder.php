<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            ['username' => 'admin', 'name' => 'Admin Desa', 'role' => 'admin', 'nik' => null],
            ['username' => 'kades', 'name' => 'Kepala Desa', 'role' => 'kades', 'nik' => null],
            // Terhubung ke penduduk hasil PendudukSeeder.
            ['username' => 'warga', 'name' => 'Warga Contoh', 'role' => 'warga', 'nik' => PendudukSeeder::NIK_WARGA_CONTOH],
        ];

        foreach ($akun as $a) {
            User::updateOrCreate(
                ['username' => $a['username']],
                $a + ['password' => 'password', 'is_active' => true],
            );
        }
    }
}

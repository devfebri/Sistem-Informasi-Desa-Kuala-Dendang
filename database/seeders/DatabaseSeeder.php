<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WilayahSeeder::class,
            PendudukSeeder::class,
            BidangTanahSeeder::class,
            UserSeeder::class,
            PeralihanHakSeeder::class,
            JenisSuratSeeder::class,
            PengajuanSuratSeeder::class,
        ]);
    }
}

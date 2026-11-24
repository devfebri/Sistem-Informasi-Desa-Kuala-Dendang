<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        $dusun = ['Dusun Mekar Jaya', 'Dusun Sido Makmur', 'Dusun Suka Maju', 'Dusun Bahari'];

        foreach ($dusun as $nama) {
            foreach (['001', '002'] as $rt) {
                Wilayah::firstOrCreate(['nama_dusun' => $nama, 'rt' => $rt, 'rw' => '001']);
            }
        }
    }
}

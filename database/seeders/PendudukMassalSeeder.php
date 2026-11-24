<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Uji performa: ±10.000 penduduk. Opsional, jalankan manual:
 *   php artisan db:seed --class=PendudukMassalSeeder
 * Memakai kode wilayah berbeda (150599) agar tidak bentrok dengan PendudukSeeder.
 */
class PendudukMassalSeeder extends Seeder
{
    public const TARGET = 10000;

    public function run(): void
    {
        $this->call(WilayahSeeder::class);

        $wilayah = Wilayah::all(['id', 'nama_dusun', 'rt', 'rw'])
            ->map(fn ($w) => ['id' => $w->id, 'label' => $w->label])->values()->all();
        $gen = new GeneratorKeluarga('150599');

        $total = 0;
        $kkBatch = [];
        $orangBatch = [];

        $simpan = function () use (&$kkBatch, &$orangBatch) {
            DB::transaction(function () use ($kkBatch, $orangBatch) {
                DB::table('kartu_keluarga')->insert($kkBatch);
                foreach (array_chunk($orangBatch, 500) as $chunk) {
                    DB::table('penduduk')->insert($chunk);
                }
            });
            $kkBatch = [];
            $orangBatch = [];
        };

        while ($total < self::TARGET) {
            $data = $gen->buat($wilayah[array_rand($wilayah)]);
            $kkBatch[] = $data['kk'];
            array_push($orangBatch, ...$data['anggota']);
            $total += count($data['anggota']);

            if (count($kkBatch) >= 200) {
                $simpan();
            }
        }

        if ($kkBatch) {
            $simpan();
        }

        $this->command?->info("Selesai: {$total} penduduk dibuat.");
    }
}

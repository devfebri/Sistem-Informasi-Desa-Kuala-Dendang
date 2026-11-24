<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** ±50 penduduk (20 KK). NIK kepala keluarga pertama = NIK akun warga contoh. */
class PendudukSeeder extends Seeder
{
    public const NIK_WARGA_CONTOH = '1505010101900001';

    public function run(): void
    {
        if (DB::table('penduduk')->exists()) {
            return;
        }

        $wilayah = Wilayah::all(['id', 'nama_dusun', 'rt', 'rw'])
            ->map(fn ($w) => ['id' => $w->id, 'label' => $w->label])->values()->all();
        $gen = new GeneratorKeluarga('150501');

        DB::transaction(function () use ($gen, $wilayah) {
            for ($i = 0; $i < 20; $i++) {
                $data = $gen->buat($wilayah[$i % count($wilayah)], $i === 0 ? self::NIK_WARGA_CONTOH : null);
                DB::table('kartu_keluarga')->insert($data['kk']);
                DB::table('penduduk')->insert($data['anggota']);
            }
        });
    }
}

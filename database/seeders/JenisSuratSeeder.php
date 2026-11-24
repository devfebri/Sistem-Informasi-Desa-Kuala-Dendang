<?php

namespace Database\Seeders;

use App\Models\JenisSurat;
use Illuminate\Database\Seeder;

class JenisSuratSeeder extends Seeder
{
    public function run(): void
    {
        $ktpKk = ['Fotokopi KTP', 'Fotokopi Kartu Keluarga'];
        $bukti = 'Fotokopi bukti kepemilikan tanah (sertifikat/girik/letter C)';
        $pbb = 'Fotokopi SPPT PBB tahun terakhir';

        $data = [
            ['kode' => 'SKT', 'nama' => 'Surat Keterangan Tanah (SKT)', 'template_view' => 'surat.skt', 'persyaratan' => [...$ktpKk, $bukti, $pbb]],
            ['kode' => 'SKRT', 'nama' => 'Surat Keterangan Riwayat Tanah', 'template_view' => 'surat.riwayat-tanah', 'persyaratan' => [...$ktpKk, $bukti]],
            ['kode' => 'SKTS', 'nama' => 'Surat Keterangan Tidak Sengketa', 'template_view' => 'surat.tidak-sengketa', 'persyaratan' => [...$ktpKk, $pbb]],
            ['kode' => 'SKJB', 'nama' => 'Surat Keterangan Jual Beli', 'template_view' => 'surat.jual-beli', 'persyaratan' => [...$ktpKk, $bukti, $pbb]],
        ];

        foreach ($data as $d) {
            JenisSurat::updateOrCreate(['kode' => $d['kode']], $d + ['is_active' => true]);
        }
    }
}

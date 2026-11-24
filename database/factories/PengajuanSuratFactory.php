<?php

namespace Database\Factories;

use App\Models\BidangTanah;
use App\Models\JenisSurat;
use App\Models\PengajuanSurat;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PengajuanSurat> */
class PengajuanSuratFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bidang_tanah_id' => BidangTanah::factory()->persegi(),
            'nik_pemohon' => fn (array $a) => BidangTanah::find($a['bidang_tanah_id'])->nik_pemilik,
            'jenis_surat_id' => fn () => JenisSurat::query()->value('id') ?? JenisSurat::create([
                'kode' => 'SKT', 'nama' => 'Surat Keterangan Tanah', 'template_view' => 'surat.skt', 'persyaratan' => ['Fotokopi KTP'],
            ])->id,
            'keperluan' => 'Pengurusan sertifikat tanah',
            'status' => 'diajukan',
        ];
    }

    public function status(string $status): static
    {
        return $this->state(['status' => $status]);
    }
}

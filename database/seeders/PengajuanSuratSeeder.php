<?php

namespace Database\Seeders;

use App\Models\BidangTanah;
use App\Models\JenisSurat;
use App\Models\PengajuanSurat;
use App\Models\User;
use App\Services\PenerbitanSurat;
use Illuminate\Database\Seeder;

/** Contoh pengajuan milik akun "warga": diajukan, diverifikasi, dan disetujui (surat PDF bisa dicoba). */
class PengajuanSuratSeeder extends Seeder
{
    public function run(): void
    {
        if (PengajuanSurat::query()->exists()) {
            return;
        }

        $warga = User::where('username', 'warga')->first();
        $admin = User::where('role', 'admin')->first();
        $kades = User::where('role', 'kades')->first();
        $bidang = $warga ? BidangTanah::where('nik_pemilik', $warga->nik)->orderBy('id')->first() : null;
        $jenis = JenisSurat::query()->orderBy('id')->get()->keyBy('kode');

        if (! $bidang || ! $admin || ! $kades || $jenis->isEmpty()) {
            return;
        }

        $buat = function (string $kode, string $keperluan, int $hariLalu) use ($warga, $bidang, $jenis) {
            $p = PengajuanSurat::create([
                'nik_pemohon' => $warga->nik,
                'jenis_surat_id' => $jenis[$kode]->id,
                'bidang_tanah_id' => $bidang->id,
                'keperluan' => $keperluan,
                'status' => 'diajukan',
                'created_at' => now()->subDays($hariLalu),
                'updated_at' => now()->subDays($hariLalu),
            ]);
            $p->update(['no_registrasi' => sprintf('REG-%s-%05d', $p->created_at->format('Ymd'), $p->id)]);

            return $p;
        };

        $buat('SKT', 'Pengurusan sertifikat tanah', 1);

        $kedua = $buat('SKTS', 'Persyaratan pengajuan kredit bank', 3);
        $kedua->update(['status' => 'diverifikasi', 'verified_by' => $admin->id, 'verified_at' => now()->subDays(2)]);

        $ketiga = $buat('SKRT', 'Kelengkapan berkas jual beli', 6);
        $ketiga->update(['status' => 'diverifikasi', 'verified_by' => $admin->id, 'verified_at' => now()->subDays(5)]);
        app(PenerbitanSurat::class)->setujui($ketiga, $kades);
    }
}

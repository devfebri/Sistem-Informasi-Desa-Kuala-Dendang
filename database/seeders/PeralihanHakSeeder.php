<?php

namespace Database\Seeders;

use App\Models\BidangTanah;
use App\Models\Penduduk;
use App\Models\PeralihanHak;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Riwayat contoh: untuk beberapa persil, pemilik saat ini diperoleh dari penduduk lain
 * (data konsisten: pemilik_baru pada riwayat terakhir = pemilik saat ini).
 */
class PeralihanHakSeeder extends Seeder
{
    public function run(): void
    {
        if (PeralihanHak::query()->exists()) {
            return;
        }

        $admin = User::where('role', 'admin')->first();
        $jenis = ['jual_beli', 'waris', 'hibah', 'jual_beli'];
        $bidang = BidangTanah::query()->orderBy('id')->skip(3)->take(4)->get();

        foreach ($bidang as $i => $b) {
            $lama = Penduduk::query()
                ->where('nik', 'like', '150501%')->where('nik', '!=', $b->nik_pemilik)->where('status', 'hidup')
                ->inRandomOrder()->value('nik');

            if (! $lama) {
                continue;
            }

            PeralihanHak::create([
                'bidang_tanah_id' => $b->id,
                'nik_pemilik_lama' => $lama,
                'nik_pemilik_baru' => $b->nik_pemilik,
                'jenis' => $jenis[$i % count($jenis)],
                'tanggal' => now()->subMonths(2 + $i * 3)->toDateString(),
                'no_akta' => $jenis[$i % count($jenis)] === 'jual_beli' ? sprintf('AKTA/%03d/%d', $i + 1, now()->year - 1) : null,
                'keterangan' => 'Data contoh.',
                'created_by' => $admin?->id,
            ]);
        }
    }
}

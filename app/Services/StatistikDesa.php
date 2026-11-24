<?php

namespace App\Services;

use App\Models\BidangTanah;
use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use App\Models\PengajuanSurat;
use App\Models\Wilayah;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Angka & data grafik dashboard. Di-cache 10 menit; dihapus otomatis saat data sumber berubah. */
class StatistikDesa
{
    private const KUNCI = 'statistik.dashboard';
    private const TTL = 600;

    public static function lupakan(): void
    {
        Cache::forget(self::KUNCI);
    }

    public function ambil(): array
    {
        return Cache::remember(self::KUNCI, self::TTL, fn () => $this->hitung());
    }

    /** Antrean kerja: sengaja tidak di-cache agar selalu terkini (query indeks, murah). */
    public function antrean(): array
    {
        return [
            'menunggu_verifikasi' => PengajuanSurat::where('status', 'diajukan')->count(),
            'menunggu_persetujuan' => PengajuanSurat::where('status', 'diverifikasi')->count(),
        ];
    }

    private function hitung(): array
    {
        $pdd = Penduduk::query()->where('status', 'hidup')
            ->selectRaw("count(*) as total, coalesce(sum(jenis_kelamin = 'L'), 0) as laki, coalesce(sum(jenis_kelamin = 'P'), 0) as perempuan")
            ->first();

        $tanah = BidangTanah::query()->selectRaw('count(*) as total, coalesce(sum(luas_m2), 0) as luas')->first();

        return [
            'dibuat' => now()->toIso8601String(),
            'penduduk' => ['total' => (int) $pdd->total, 'laki' => (int) $pdd->laki, 'perempuan' => (int) $pdd->perempuan],
            'kk' => KartuKeluarga::count(),
            'persil' => ['total' => (int) $tanah->total, 'luas_m2' => (float) $tanah->luas],
            'status_hak' => $this->statusHak(),
            'penggunaan_per_dusun' => $this->penggunaanPerDusun(),
            'surat_per_bulan' => $this->suratPerBulan(),
            'surat_per_status' => $this->suratPerStatus(),
        ];
    }

    /** @return array<int, array{label:string, persil:int, luas_m2:float}> */
    private function statusHak(): array
    {
        $baris = BidangTanah::query()->selectRaw('status_hak, count(*) as n, coalesce(sum(luas_m2), 0) as luas')
            ->groupBy('status_hak')->get()->keyBy('status_hak');

        return collect(BidangTanah::STATUS_HAK)->map(fn ($s) => [
            'label' => $s,
            'persil' => (int) ($baris[$s]->n ?? 0),
            'luas_m2' => (float) ($baris[$s]->luas ?? 0),
        ])->all();
    }

    /** Luas (m²) per penggunaan lahan di tiap dusun. */
    private function penggunaanPerDusun(): array
    {
        $dusun = Wilayah::query()->orderBy('nama_dusun')->pluck('nama_dusun')->unique()->values();

        $baris = BidangTanah::query()
            ->join('wilayah', 'wilayah.id', '=', 'bidang_tanah.wilayah_id')
            ->selectRaw('wilayah.nama_dusun as dusun, bidang_tanah.penggunaan_lahan as guna, sum(bidang_tanah.luas_m2) as luas')
            ->groupBy('wilayah.nama_dusun', 'bidang_tanah.penggunaan_lahan')
            ->get();

        $peta = [];
        foreach ($baris as $b) {
            $peta[$b->dusun][$b->guna] = (float) $b->luas;
        }

        return [
            'dusun' => $dusun->all(),
            'seri' => collect(BidangTanah::PENGGUNAAN)->map(fn ($g) => [
                'label' => ucfirst($g),
                'nilai' => $dusun->map(fn ($d) => $peta[$d][$g] ?? 0)->all(),
            ])->all(),
        ];
    }

    /** 12 bulan terakhir (termasuk bulan ini), bulan tanpa data bernilai 0. */
    private function suratPerBulan(): array
    {
        $mulai = now()->startOfMonth()->subMonths(11);
        $ekspresi = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "date_format(created_at, '%Y-%m')";

        $baris = PengajuanSurat::query()->where('created_at', '>=', $mulai)
            ->selectRaw("$ekspresi as bulan, count(*) as n")->groupBy('bulan')->pluck('n', 'bulan');

        $label = [];
        $nilai = [];
        for ($i = 0; $i < 12; $i++) {
            $b = Carbon::parse($mulai)->addMonths($i);
            $label[] = $b->translatedFormat('M Y');
            $nilai[] = (int) ($baris[$b->format('Y-m')] ?? 0);
        }

        return ['label' => $label, 'nilai' => $nilai];
    }

    private function suratPerStatus(): array
    {
        $baris = PengajuanSurat::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return collect(PengajuanSurat::STATUS)->map(fn ($label, $k) => [
            'label' => $label, 'nilai' => (int) ($baris[$k] ?? 0),
        ])->values()->all();
    }
}

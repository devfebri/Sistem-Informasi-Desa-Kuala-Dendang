<?php

namespace App\Http\Controllers;

use App\Models\BidangTanah;
use App\Models\Penduduk;
use App\Models\PengajuanSurat;
use App\Services\StatistikDesa;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function admin(StatistikDesa $statistik)
    {
        return view('dashboard.admin', ['stat' => $statistik->ambil(), 'antrean' => $statistik->antrean()]);
    }

    public function kades(StatistikDesa $statistik)
    {
        return view('dashboard.kades', ['stat' => $statistik->ambil(), 'antrean' => $statistik->antrean()]);
    }

    public function warga(Request $request)
    {
        $nik = $request->user()->nik ?? '';

        return view('dashboard.warga', [
            'penduduk' => Penduduk::query()->where('nik', $nik)->first(['id', 'nik', 'nama']),
            'jumlahBidang' => BidangTanah::where('nik_pemilik', $nik)->count(),
            'diproses' => PengajuanSurat::where('nik_pemohon', $nik)->whereIn('status', PengajuanSurat::AKTIF)->count(),
            'suratTerbit' => PengajuanSurat::where('nik_pemohon', $nik)->whereIn('status', ['disetujui', 'selesai'])->count(),
            'terakhir' => PengajuanSurat::query()->where('nik_pemohon', $nik)
                ->with('jenisSurat:id,nama')->latest()->limit(5)
                ->get(['id', 'no_registrasi', 'jenis_surat_id', 'status', 'created_at']),
        ]);
    }
}

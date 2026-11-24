<?php

namespace App\Http\Controllers;

use App\Models\PengajuanSurat;
use App\Support\Samar;

/** Halaman publik hasil pindai QR pada surat: /validasi/{kode}. Data pribadi disamarkan. */
class ValidasiController extends Controller
{
    public function show(string $kode)
    {
        $p = PengajuanSurat::query()
            ->where('kode_validasi', strtoupper($kode))
            ->whereIn('status', ['disetujui', 'selesai'])
            ->with(['pemohon:id,nik,nama', 'jenisSurat:id,nama', 'bidangTanah:id,no_persil,luas_m2', 'penyetuju:id,name'])
            ->first();

        return view('validasi.hasil', [
            'p' => $p,
            'kode' => $kode,
            'namaSamar' => $p ? Samar::nama($p->pemohon?->nama) : null,
        ]);
    }
}

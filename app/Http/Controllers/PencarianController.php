<?php

namespace App\Http\Controllers;

use App\Models\BidangTanah;
use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Endpoint Select2 AJAX: { results: [{id, text}], pagination: { more } } */
class PencarianController extends Controller
{
    private const PER_HALAMAN = 20;

    public function penduduk(Request $request): JsonResponse
    {
        $kata = trim((string) $request->input('q'));

        $hasil = Penduduk::query()
            ->select(['id', 'nik', 'nama', 'alamat'])
            ->when($kata !== '', function ($q) use ($kata) {
                ctype_digit($kata)
                    ? $q->where('nik', 'like', $kata.'%')
                    : $q->where('nama', 'like', $kata.'%');
            })
            ->orderBy('nama')
            ->simplePaginate(self::PER_HALAMAN);

        return response()->json([
            'results' => $hasil->map(fn ($p) => [
                'id' => $p->nik,
                'text' => "{$p->nik} — {$p->nama}",
                'sub' => $p->alamat, // ditampilkan sebagai baris kedua di dropdown
            ])->values(),
            'pagination' => ['more' => $hasil->hasMorePages()],
        ]);
    }

    /** Bidang tanah beserta pemilik saat ini (untuk form peralihan hak). */
    public function bidangTanah(Request $request): JsonResponse
    {
        $kata = trim((string) $request->input('q'));

        $hasil = BidangTanah::query()
            ->select(['id', 'no_persil', 'nik_pemilik', 'luas_m2'])
            ->with('pemilik:id,nik,nama')
            ->when($kata !== '', fn ($q) => $q->where(fn ($w) => $w->where('no_persil', 'like', $kata.'%')
                ->orWhere('nik_pemilik', 'like', $kata.'%')))
            ->orderBy('no_persil')
            ->simplePaginate(self::PER_HALAMAN);

        return response()->json([
            'results' => $hasil->map(fn ($b) => [
                'id' => $b->id,
                'text' => $b->no_persil.' — '.($b->pemilik?->nama ?? $b->nik_pemilik),
                'sub' => 'NIK pemilik: '.$b->nik_pemilik.' · '.number_format($b->luas_m2, 0, ',', '.').' m²',
                'pemilik_nik' => $b->nik_pemilik,
                'pemilik_nama' => $b->pemilik?->nama,
            ])->values(),
            'pagination' => ['more' => $hasil->hasMorePages()],
        ]);
    }

    public function kartuKeluarga(Request $request): JsonResponse
    {
        $kata = trim((string) $request->input('q'));

        $hasil = KartuKeluarga::query()
            ->select(['id', 'no_kk', 'nik_kepala_keluarga'])
            ->with('kepala:id,nik,nama')
            ->when($kata !== '', fn ($q) => $q->where('no_kk', 'like', $kata.'%'))
            ->orderBy('no_kk')
            ->simplePaginate(self::PER_HALAMAN);

        return response()->json([
            'results' => $hasil->map(fn ($k) => [
                'id' => $k->no_kk,
                'text' => $k->no_kk.($k->kepala ? " — {$k->kepala->nama}" : ''),
            ])->values(),
            'pagination' => ['more' => $hasil->hasMorePages()],
        ]);
    }
}

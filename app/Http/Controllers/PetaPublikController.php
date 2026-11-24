<?php

namespace App\Http\Controllers;

use App\Models\BidangTanah;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Peta untuk warga: hanya data yang aman dilihat umum.
 * Tidak ada NIK, nomor sertifikat, atau nama pemilik asli (disamarkan); pencarian hanya dengan no. persil.
 */
class PetaPublikController extends Controller
{
    private const BATAS_FITUR = 3000;

    public function index()
    {
        return view('peta.index', [
            'publik' => true,
            'wilayah' => Wilayah::options(),
            'statusHak' => BidangTanah::STATUS_HAK,
            'penggunaan' => BidangTanah::PENGGUNAAN,
            'urls' => [
                'geojson' => route('warga.api.geojson'),
                'wilayah' => route('api.wilayah.publik'),
                'popup' => route('warga.api.popup', ['bidangTanah' => '__ID__']),
                'cari' => route('warga.api.cari'),
            ],
        ]);
    }

    public function geojson(Request $request): JsonResponse
    {
        $nik = $request->user()->nik;

        $query = BidangTanah::query()
            ->select(['id', 'no_persil', 'nik_pemilik', 'status_hak', 'penggunaan_lahan', 'geojson'])
            ->whereNotNull('geojson')
            ->dalamBbox($request->input('bbox'));

        foreach (['wilayah_id', 'status_hak', 'penggunaan_lahan'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->input($f));
            }
        }

        $rows = $query->limit(self::BATAS_FITUR + 1)->get();

        return response()->json([
            'type' => 'FeatureCollection',
            'truncated' => $rows->count() > self::BATAS_FITUR,
            'features' => $rows->take(self::BATAS_FITUR)->map(fn ($b) => [
                'type' => 'Feature',
                'id' => $b->id,
                'geometry' => $b->geojson,
                'properties' => [
                    'id' => $b->id,
                    'no_persil' => $b->no_persil,
                    'status_hak' => $b->status_hak,
                    'penggunaan_lahan' => $b->penggunaan_lahan,
                    'milik_saya' => $nik !== null && $b->nik_pemilik === $nik, // NIK sendiri tidak dikirim
                ],
            ])->values(),
        ]);
    }

    public function popup(Request $request, BidangTanah $bidangTanah)
    {
        $bidangTanah->load(['pemilik:id,nik,nama', 'wilayah:id,nama_dusun,rt,rw']);

        return view('peta._popup_publik', [
            'b' => $bidangTanah,
            'milikSaya' => $request->user()->nik !== null && $bidangTanah->nik_pemilik === $request->user()->nik,
        ]);
    }

    /** Pencarian hanya berdasarkan no. persil (bukan NIK/nama, agar tidak membuka identitas pemilik). */
    public function cari(Request $request): JsonResponse
    {
        $kata = trim((string) $request->input('q'));

        $hasil = BidangTanah::query()
            ->select(['id', 'no_persil', 'min_lng', 'min_lat', 'max_lng', 'max_lat'])
            ->whereNotNull('geojson')
            ->when($kata !== '', fn ($q) => $q->where('no_persil', 'like', $kata.'%'))
            ->orderBy('no_persil')
            ->simplePaginate(20);

        return response()->json([
            'results' => $hasil->map(fn ($b) => [
                'id' => $b->id,
                'text' => $b->no_persil,
                'bbox' => [(float) $b->min_lng, (float) $b->min_lat, (float) $b->max_lng, (float) $b->max_lat],
            ])->values(),
            'pagination' => ['more' => $hasil->hasMorePages()],
        ]);
    }
}

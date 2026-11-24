<?php

namespace App\Http\Controllers;

use App\Http\Requests\BidangTanahRequest;
use App\Models\BidangTanah;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BidangTanahController extends Controller
{
    private const BATAS_FITUR = 3000;

    private const WARNA_STATUS = [
        'SHM' => '#198754', 'HGB' => '#0d6efd', 'SKT' => '#fd7e14',
        'Girik' => '#6f42c1', 'Letter C' => '#d63384', 'Belum bersertifikat' => '#dc3545',
    ];

    public function index()
    {
        return view('bidang-tanah.index', [
            'wilayah' => Wilayah::options(),
            'statusHak' => BidangTanah::STATUS_HAK,
            'penggunaan' => BidangTanah::PENGGUNAAN,
        ]);
    }

    /** Endpoint tabel: tanpa kolom geojson. */
    public function data(Request $request)
    {
        $query = BidangTanah::query()
            ->select(['id', 'no_persil', 'nik_pemilik', 'luas_m2', 'status_hak', 'penggunaan_lahan', 'wilayah_id'])
            ->with(['pemilik:id,nik,nama', 'wilayah:id,nama_dusun,rt,rw']);

        foreach (['wilayah_id', 'status_hak', 'penggunaan_lahan'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->input($f));
            }
        }

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request) {
                $kata = trim((string) $request->input('search.value'));
                if ($kata !== '') {
                    $q->where(fn ($w) => $w->where('no_persil', 'like', $kata.'%')
                        ->orWhere('nik_pemilik', 'like', $kata.'%'));
                }
            })
            ->addIndexColumn()
            ->addColumn('pemilik', fn (BidangTanah $b) => e($b->pemilik?->nama ?? '-').'<br><small class="text-muted">'.e($b->nik_pemilik).'</small>')
            ->editColumn('luas_m2', fn (BidangTanah $b) => number_format($b->luas_m2, 0, ',', '.').' m²')
            ->editColumn('status_hak', fn (BidangTanah $b) => '<span class="badge" style="background:'.(self::WARNA_STATUS[$b->status_hak] ?? '#6c757d').'">'.e($b->status_hak).'</span>')
            ->editColumn('penggunaan_lahan', fn (BidangTanah $b) => e(ucfirst($b->penggunaan_lahan)))
            ->addColumn('dusun', fn (BidangTanah $b) => e($b->wilayah?->label ?? '-'))
            ->addColumn('aksi', fn (BidangTanah $b) => view('bidang-tanah._aksi', ['id' => $b->id])->render())
            ->rawColumns(['pemilik', 'status_hak', 'aksi'])
            ->toJson();
    }

    public function create()
    {
        return view('bidang-tanah.form', $this->formData(new BidangTanah(['status_hak' => 'SKT', 'penggunaan_lahan' => 'pemukiman'])));
    }

    public function store(BidangTanahRequest $request): JsonResponse
    {
        BidangTanah::create($request->payload());

        return response()->json([
            'message' => 'Bidang tanah berhasil ditambahkan.',
            'redirect' => route('admin.bidang-tanah.index'),
        ]);
    }

    public function show(BidangTanah $bidangTanah)
    {
        $bidangTanah->load(['pemilik:id,nik,nama,alamat', 'wilayah:id,nama_dusun,rt,rw']);

        $riwayat = $bidangTanah->peralihanHak()
            ->with(['pemilikLama:id,nik,nama', 'pemilikBaru:id,nik,nama', 'pencatat:id,name'])
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->get();

        return view('bidang-tanah.show', ['b' => $bidangTanah, 'riwayat' => $riwayat]);
    }

    public function edit(BidangTanah $bidangTanah)
    {
        $bidangTanah->load('pemilik:id,nik,nama');

        return view('bidang-tanah.form', $this->formData($bidangTanah));
    }

    public function update(BidangTanahRequest $request, BidangTanah $bidangTanah): JsonResponse
    {
        $bidangTanah->update($request->payload());

        return response()->json([
            'message' => 'Bidang tanah berhasil diperbarui.',
            'redirect' => route('admin.bidang-tanah.index'),
        ]);
    }

    public function destroy(BidangTanah $bidangTanah): JsonResponse
    {
        if ($bidangTanah->peralihanHak()->exists()) {
            return response()->json(['message' => 'Bidang tanah memiliki riwayat peralihan hak, tidak dapat dihapus.'], 422);
        }
        if (\App\Models\PengajuanSurat::where('bidang_tanah_id', $bidangTanah->id)->exists()) {
            return response()->json(['message' => 'Bidang tanah dipakai pada pengajuan surat, tidak dapat dihapus.'], 422);
        }

        $bidangTanah->delete();

        return response()->json(['message' => 'Bidang tanah berhasil dihapus.']);
    }

    // ---------------------------------------------------------------- API peta

    /**
     * FeatureCollection bidang tanah. Parameter: bbox=minLng,minLat,maxLng,maxLat,
     * wilayah_id, status_hak, penggunaan_lahan, exclude (id).
     */
    public function geojson(Request $request): JsonResponse
    {
        $query = BidangTanah::query()
            ->select(['id', 'no_persil', 'status_hak', 'penggunaan_lahan', 'geojson'])
            ->whereNotNull('geojson');

        if ($bbox = $this->parseBbox($request->input('bbox'))) {
            [$minLng, $minLat, $maxLng, $maxLat] = $bbox;
            $query->where('max_lng', '>=', $minLng)->where('min_lng', '<=', $maxLng)
                ->where('max_lat', '>=', $minLat)->where('min_lat', '<=', $maxLat);
        }
        foreach (['wilayah_id', 'status_hak', 'penggunaan_lahan'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->input($f));
            }
        }
        if ($request->filled('exclude')) {
            $query->whereKeyNot($request->integer('exclude'));
        }

        $rows = $query->limit(self::BATAS_FITUR + 1)->get();
        $terpotong = $rows->count() > self::BATAS_FITUR;

        return response()->json([
            'type' => 'FeatureCollection',
            'truncated' => $terpotong,
            'features' => $rows->take(self::BATAS_FITUR)->map(fn ($b) => [
                'type' => 'Feature',
                'id' => $b->id,
                'geometry' => $b->geojson,
                'properties' => [
                    'id' => $b->id,
                    'no_persil' => $b->no_persil,
                    'status_hak' => $b->status_hak,
                    'penggunaan_lahan' => $b->penggunaan_lahan,
                ],
            ])->values(),
        ]);
    }

    /** Batas dusun: satu fitur per nama dusun (geometri pertama yang tersedia). */
    public function wilayahGeojson(): JsonResponse
    {
        $fitur = Wilayah::query()->whereNotNull('geojson')->get(['id', 'nama_dusun', 'geojson'])
            ->unique('nama_dusun')
            ->map(fn ($w) => [
                'type' => 'Feature',
                'geometry' => $w->geojson,
                'properties' => ['nama' => $w->nama_dusun],
            ])->values();

        return response()->json(['type' => 'FeatureCollection', 'features' => $fitur]);
    }

    /** HTML popup detail persil (dimuat saat polygon diklik). */
    public function popup(BidangTanah $bidangTanah)
    {
        $bidangTanah->load(['pemilik:id,nik,nama', 'wilayah:id,nama_dusun,rt,rw']);

        return view('bidang-tanah._popup', ['b' => $bidangTanah]);
    }

    /** Pencarian untuk peta (Select2): no. persil atau NIK pemilik, beserta bbox untuk zoom. */
    public function cari(Request $request): JsonResponse
    {
        $kata = trim((string) $request->input('q'));

        $hasil = BidangTanah::query()
            ->select(['id', 'no_persil', 'nik_pemilik', 'min_lng', 'min_lat', 'max_lng', 'max_lat'])
            ->with('pemilik:id,nik,nama')
            ->whereNotNull('geojson')
            ->when($kata !== '', fn ($q) => $q->where(fn ($w) => $w->where('no_persil', 'like', $kata.'%')
                ->orWhere('nik_pemilik', 'like', $kata.'%')))
            ->orderBy('no_persil')
            ->simplePaginate(20);

        return response()->json([
            'results' => $hasil->map(fn ($b) => [
                'id' => $b->id,
                'text' => $b->no_persil.' — '.($b->pemilik?->nama ?? $b->nik_pemilik),
                'bbox' => [(float) $b->min_lng, (float) $b->min_lat, (float) $b->max_lng, (float) $b->max_lat],
            ])->values(),
            'pagination' => ['more' => $hasil->hasMorePages()],
        ]);
    }

    private function formData(BidangTanah $b): array
    {
        return [
            'b' => $b,
            'wilayah' => Wilayah::options(),
            'statusHak' => BidangTanah::STATUS_HAK,
            'penggunaan' => BidangTanah::PENGGUNAAN,
        ];
    }

    /** @return array{0:float,1:float,2:float,3:float}|null */
    private function parseBbox(?string $bbox): ?array
    {
        if (! $bbox) {
            return null;
        }
        $p = array_map('floatval', explode(',', $bbox));

        return count($p) === 4 ? $p : null;
    }
}

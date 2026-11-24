<?php

namespace App\Http\Controllers;

use App\Http\Requests\WilayahRequest;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class WilayahController extends Controller
{
    public function index()
    {
        return view('wilayah.index');
    }

    public function data(Request $request)
    {
        $query = Wilayah::query()
            ->select(['id', 'nama_dusun', 'rt', 'rw'])
            ->withCount(['penduduk', 'kartuKeluarga']);

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request) {
                $kata = trim((string) $request->input('search.value'));
                if ($kata !== '') {
                    $q->where('nama_dusun', 'like', $kata.'%');
                }
            })
            ->addIndexColumn()
            ->addColumn('aksi', fn (Wilayah $w) => view('partials.aksi', ['id' => $w->id])->render())
            ->rawColumns(['aksi'])
            ->toJson();
    }

    public function store(WilayahRequest $request): JsonResponse
    {
        Wilayah::create($request->payload());

        return response()->json(['message' => 'Wilayah berhasil ditambahkan.']);
    }

    public function edit(Wilayah $wilayah): JsonResponse
    {
        return response()->json($wilayah->only(['id', 'nama_dusun', 'rt', 'rw']) + [
            'geojson' => $wilayah->geojson ? json_encode($wilayah->geojson) : '',
        ]);
    }

    public function update(WilayahRequest $request, Wilayah $wilayah): JsonResponse
    {
        $wilayah->update($request->payload());

        return response()->json(['message' => 'Wilayah berhasil diperbarui.']);
    }

    public function destroy(Wilayah $wilayah): JsonResponse
    {
        if ($wilayah->penduduk()->exists() || $wilayah->kartuKeluarga()->exists()) {
            return response()->json(['message' => 'Wilayah masih dipakai oleh data penduduk/KK, tidak dapat dihapus.'], 422);
        }

        $wilayah->delete();

        return response()->json(['message' => 'Wilayah berhasil dihapus.']);
    }
}

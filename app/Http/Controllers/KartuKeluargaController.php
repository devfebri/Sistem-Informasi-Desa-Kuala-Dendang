<?php

namespace App\Http\Controllers;

use App\Http\Requests\KartuKeluargaRequest;
use App\Models\KartuKeluarga;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class KartuKeluargaController extends Controller
{
    public function index()
    {
        return view('kartu-keluarga.index', ['wilayah' => Wilayah::options()]);
    }

    public function data(Request $request)
    {
        $query = KartuKeluarga::query()
            ->select(['id', 'no_kk', 'nik_kepala_keluarga', 'alamat', 'wilayah_id'])
            ->with(['kepala:id,nik,nama', 'wilayah:id,nama_dusun,rt,rw'])
            ->withCount('anggota');

        if ($request->filled('wilayah_id')) {
            $query->where('wilayah_id', $request->input('wilayah_id'));
        }

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request) {
                $kata = trim((string) $request->input('search.value'));
                if ($kata !== '') {
                    $q->where(fn ($w) => $w->where('no_kk', 'like', $kata.'%')
                        ->orWhere('nik_kepala_keluarga', 'like', $kata.'%'));
                }
            })
            ->addIndexColumn()
            ->addColumn('kepala', fn (KartuKeluarga $k) => e($k->kepala?->nama ?? '-'))
            ->addColumn('dusun', fn (KartuKeluarga $k) => e($k->wilayah?->label ?? '-'))
            ->addColumn('aksi', fn (KartuKeluarga $k) => view('partials.aksi', ['id' => $k->id])->render())
            ->rawColumns(['aksi'])
            ->toJson();
    }

    public function store(KartuKeluargaRequest $request): JsonResponse
    {
        KartuKeluarga::create($request->validated());

        return response()->json(['message' => 'Kartu keluarga berhasil ditambahkan.']);
    }

    public function edit(KartuKeluarga $kartuKeluarga): JsonResponse
    {
        $kartuKeluarga->load('kepala:id,nik,nama');

        return response()->json([
            ...$kartuKeluarga->only(['id', 'no_kk', 'nik_kepala_keluarga', 'alamat', 'wilayah_id']),
            'kepala_text' => $kartuKeluarga->kepala ? "{$kartuKeluarga->kepala->nik} — {$kartuKeluarga->kepala->nama}" : null,
        ]);
    }

    public function update(KartuKeluargaRequest $request, KartuKeluarga $kartuKeluarga): JsonResponse
    {
        $kartuKeluarga->update($request->validated());

        return response()->json(['message' => 'Kartu keluarga berhasil diperbarui.']);
    }

    public function destroy(KartuKeluarga $kartuKeluarga): JsonResponse
    {
        if ($kartuKeluarga->anggota()->exists()) {
            return response()->json(['message' => 'KK masih memiliki anggota penduduk, tidak dapat dihapus.'], 422);
        }

        $kartuKeluarga->delete();

        return response()->json(['message' => 'Kartu keluarga berhasil dihapus.']);
    }
}

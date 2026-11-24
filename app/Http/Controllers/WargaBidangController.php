<?php

namespace App\Http\Controllers;

use App\Models\BidangTanah;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/** Bidang tanah milik warga yang sedang login. */
class WargaBidangController extends Controller
{
    public function index()
    {
        return view('warga.bidang.index');
    }

    public function data(Request $request)
    {
        $query = BidangTanah::query()
            ->select(['id', 'no_persil', 'luas_m2', 'status_hak', 'no_sertifikat', 'penggunaan_lahan', 'wilayah_id'])
            ->where('nik_pemilik', $request->user()->nik ?? '')
            ->with('wilayah:id,nama_dusun,rt,rw');

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request) {
                $kata = trim((string) $request->input('search.value'));
                if ($kata !== '') {
                    $q->where('no_persil', 'like', $kata.'%');
                }
            })
            ->addIndexColumn()
            ->editColumn('luas_m2', fn (BidangTanah $b) => number_format($b->luas_m2, 0, ',', '.').' m²')
            ->editColumn('no_sertifikat', fn (BidangTanah $b) => e($b->no_sertifikat ?? '-'))
            ->editColumn('penggunaan_lahan', fn (BidangTanah $b) => e(ucfirst($b->penggunaan_lahan)))
            ->addColumn('dusun', fn (BidangTanah $b) => e($b->wilayah?->label ?? '-'))
            ->toJson();
    }
}

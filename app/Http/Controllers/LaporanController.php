<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Reports\Laporan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/** Laporan (Buku Tanah, Rekap Peralihan, Rekap Surat): pratinjau AJAX, PDF, dan Excel. */
class LaporanController extends Controller
{
    /** Batas baris PDF agar memori/waktu terjaga; Excel tidak dibatasi. */
    private const BATAS_PDF = 2000;

    public function index(Request $request, string $laporan)
    {
        $l = Laporan::dari($laporan);

        return view('laporan.index', [
            'slug' => $laporan,
            'l' => $l,
            'nilai' => array_merge($l->nilaiAwal(), $request->only(array_column($l->filter(), 'name'))),
            'semua' => array_keys(Laporan::DAFTAR),
        ]);
    }

    /** Format DataTables server-side (tanpa pencarian; filter lewat form). */
    public function data(Request $request, string $laporan): JsonResponse
    {
        $l = Laporan::dari($laporan);
        $f = $this->filter($request, $l);

        $query = $l->query($f);
        $total = (clone $query)->toBase()->cloneWithout(['columns', 'orders'])->getCountForPagination();

        $rows = $query->offset(max(0, $request->integer('start')))
            ->limit(min(100, max(1, $request->integer('length', 10))))
            ->get()
            ->map(fn ($m) => array_map(fn ($v) => e((string) $v), $l->baris($m)))
            ->all();

        return response()->json([
            'draw' => $request->integer('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $rows,
        ]);
    }

    public function pdf(Request $request, string $laporan)
    {
        $l = Laporan::dari($laporan);
        $f = $this->filter($request, $l);

        $total = (clone $l->query($f))->toBase()->cloneWithout(['columns', 'orders'])->getCountForPagination();
        $baris = $l->query($f)->limit(self::BATAS_PDF)->get()->map(fn ($m) => $l->baris($m))->all();

        return Pdf::loadView('laporan.pdf', [
            'l' => $l,
            'baris' => $baris,
            'total' => $total,
            'terpotong' => $total > self::BATAS_PDF,
            'batas' => self::BATAS_PDF,
            'ringkasan' => $l->ringkasan($f),
            'deskripsi' => $l->deskripsiFilter($f),
            'desa' => config('desa'),
            'pencetak' => $request->user()->name,
        ])->setPaper('a4', 'landscape')->stream($l->berkas('pdf'));
    }

    public function excel(Request $request, string $laporan)
    {
        $l = Laporan::dari($laporan);

        return Excel::download(new LaporanExport($l, $this->filter($request, $l)), $l->berkas('xlsx'));
    }

    private function filter(Request $request, Laporan $l): array
    {
        return $request->validate($l->aturan());
    }
}

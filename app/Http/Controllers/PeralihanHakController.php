<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeralihanHakRequest;
use App\Models\BidangTanah;
use App\Models\PeralihanHak;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class PeralihanHakController extends Controller
{
    public function index(Request $request)
    {
        $awal = null;
        if ($request->filled('bidang_tanah_id')) {
            $b = BidangTanah::with('pemilik:id,nik,nama')->find($request->integer('bidang_tanah_id'));
            $awal = $b ? [
                'id' => $b->id,
                'text' => $b->no_persil.' — '.($b->pemilik?->nama ?? $b->nik_pemilik),
                'pemilik_nik' => $b->nik_pemilik,
                'pemilik_nama' => $b->pemilik?->nama,
            ] : null;
        }

        return view('peralihan-hak.index', ['jenis' => PeralihanHak::JENIS, 'bidangAwal' => $awal]);
    }

    public function data(Request $request)
    {
        $query = PeralihanHak::query()
            ->select(['id', 'bidang_tanah_id', 'nik_pemilik_lama', 'nik_pemilik_baru', 'jenis', 'tanggal', 'no_akta', 'dokumen', 'created_by'])
            ->with([
                'bidangTanah:id,no_persil',
                'pemilikLama:id,nik,nama',
                'pemilikBaru:id,nik,nama',
                'pencatat:id,name',
            ]);

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->input('jenis'));
        }
        if ($request->filled('bidang_tanah_id')) {
            $query->where('bidang_tanah_id', $request->input('bidang_tanah_id'));
        }

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request) {
                $kata = trim((string) $request->input('search.value'));
                if ($kata !== '') {
                    $q->where(fn ($w) => $w
                        ->whereHas('bidangTanah', fn ($b) => $b->where('no_persil', 'like', $kata.'%'))
                        ->orWhere('nik_pemilik_lama', 'like', $kata.'%')
                        ->orWhere('nik_pemilik_baru', 'like', $kata.'%')
                        ->orWhere('no_akta', 'like', $kata.'%'));
                }
            })
            ->addIndexColumn()
            ->editColumn('tanggal', fn (PeralihanHak $p) => $p->tanggal->format('d-m-Y'))
            ->addColumn('persil', fn (PeralihanHak $p) => $p->bidangTanah
                ? '<a href="'.route('admin.bidang-tanah.show', $p->bidang_tanah_id).'">'.e($p->bidangTanah->no_persil).'</a>' : '-')
            ->addColumn('lama', fn (PeralihanHak $p) => $this->orang($p->pemilikLama, $p->nik_pemilik_lama))
            ->addColumn('baru', fn (PeralihanHak $p) => $this->orang($p->pemilikBaru, $p->nik_pemilik_baru))
            ->editColumn('jenis', fn (PeralihanHak $p) => '<span class="badge text-bg-'.match ($p->jenis) {
                'jual_beli' => 'primary', 'waris' => 'success', 'hibah' => 'info', default => 'secondary',
            }.'">'.e($p->jenisLabel()).'</span>')
            ->editColumn('no_akta', fn (PeralihanHak $p) => e($p->no_akta ?? '-'))
            ->addColumn('berkas', fn (PeralihanHak $p) => $p->dokumen
                ? '<a class="btn btn-sm btn-outline-secondary" target="_blank" href="'.route('admin.peralihan-hak.dokumen', $p->id).'" title="Lihat dokumen"><i class="bi bi-file-earmark-text"></i></a>'
                : '<span class="text-muted">-</span>')
            ->addColumn('pencatat', fn (PeralihanHak $p) => e($p->pencatat?->name ?? '-'))
            ->rawColumns(['persil', 'lama', 'baru', 'jenis', 'berkas'])
            ->toJson();
    }

    /**
     * Simpan riwayat lalu perbarui pemilik pada bidang tanah, dalam satu transaksi.
     * Baris bidang dikunci agar dua peralihan bersamaan tidak saling menimpa.
     */
    public function store(PeralihanHakRequest $request): JsonResponse
    {
        $data = $request->validated();
        $path = $request->file('dokumen')?->store('peralihan-hak');

        try {
            DB::transaction(function () use ($data, $path, $request) {
                $bidang = BidangTanah::query()->lockForUpdate()->findOrFail($data['bidang_tanah_id']);

                if ($bidang->nik_pemilik !== $data['nik_pemilik_lama']) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Pemilik bidang tanah ini telah berubah. Muat ulang halaman lalu coba lagi.',
                    ], 422));
                }

                PeralihanHak::create([
                    'bidang_tanah_id' => $bidang->id,
                    'nik_pemilik_lama' => $bidang->nik_pemilik,
                    'nik_pemilik_baru' => $data['nik_pemilik_baru'],
                    'jenis' => $data['jenis'],
                    'tanggal' => $data['tanggal'],
                    'no_akta' => $data['no_akta'] ?? null,
                    'dokumen' => $path,
                    'keterangan' => $data['keterangan'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                $bidang->update(['nik_pemilik' => $data['nik_pemilik_baru']]);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::delete($path);
            }
            throw $e;
        }

        return response()->json(['message' => 'Peralihan hak berhasil dicatat. Pemilik bidang tanah telah diperbarui.']);
    }

    /** Unduh/lihat dokumen (disimpan di disk privat, hanya lewat route ini). */
    public function dokumen(PeralihanHak $peralihanHak)
    {
        abort_unless($peralihanHak->dokumen && Storage::exists($peralihanHak->dokumen), 404, 'Dokumen tidak ditemukan.');

        return Storage::response($peralihanHak->dokumen);
    }

    private function orang($p, string $nik): string
    {
        return e($p?->nama ?? '-').'<br><small class="text-muted">'.e($nik).'</small>';
    }
}

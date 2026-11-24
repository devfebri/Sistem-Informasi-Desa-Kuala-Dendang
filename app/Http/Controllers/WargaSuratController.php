<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengajuanSuratRequest;
use App\Models\BidangTanah;
use App\Models\DokumenPengajuan;
use App\Models\JenisSurat;
use App\Models\PengajuanSurat;
use App\Models\Penduduk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

/** Layanan surat untuk warga: ajukan, daftar, lacak status. */
class WargaSuratController extends Controller
{
    public function index(Request $request)
    {
        $nik = $request->user()->nik;

        return view('warga.surat.index', [
            'pemohon' => $nik ? Penduduk::where('nik', $nik)->first(['id', 'nik', 'nama', 'status']) : null,
            'jenis' => JenisSurat::aktif(),
            'bidang' => $nik
                ? BidangTanah::where('nik_pemilik', $nik)->orderBy('no_persil')->limit(200)->get(['id', 'no_persil', 'luas_m2', 'status_hak'])
                : collect(),
        ]);
    }

    public function data(Request $request)
    {
        $query = PengajuanSurat::query()
            ->select(['id', 'no_registrasi', 'jenis_surat_id', 'bidang_tanah_id', 'status', 'nomor_surat', 'created_at'])
            ->where('nik_pemohon', $request->user()->nik ?? '')
            ->with(['jenisSurat:id,nama', 'bidangTanah:id,no_persil']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request) {
                $kata = trim((string) $request->input('search.value'));
                if ($kata !== '') {
                    $q->where('no_registrasi', 'like', $kata.'%');
                }
            })
            ->addIndexColumn()
            ->editColumn('created_at', fn (PengajuanSurat $p) => $p->created_at->format('d-m-Y H:i'))
            ->addColumn('jenis', fn (PengajuanSurat $p) => e($p->jenisSurat?->nama ?? '-'))
            ->addColumn('persil', fn (PengajuanSurat $p) => e($p->bidangTanah?->no_persil ?? '-'))
            ->editColumn('status', fn (PengajuanSurat $p) => $p->statusBadge())
            ->addColumn('aksi', fn (PengajuanSurat $p) => '<a href="'.route('warga.surat.show', $p).'" class="btn btn-sm btn-info text-white" title="Lacak / detail"><i class="bi bi-eye"></i></a>'
                .($p->suratTerbit() ? ' <a href="'.route('surat.pdf', $p).'" target="_blank" class="btn btn-sm btn-success" title="Unduh surat"><i class="bi bi-file-earmark-pdf"></i></a>' : ''))
            ->rawColumns(['status', 'aksi'])
            ->toJson();
    }

    public function store(PengajuanSuratRequest $request): JsonResponse
    {
        $nik = $request->user()->nik;
        $persyaratan = $request->persyaratan();
        $disimpan = [];

        try {
            $pengajuan = DB::transaction(function () use ($request, $nik, $persyaratan, &$disimpan) {
                $p = PengajuanSurat::create([
                    'nik_pemohon' => $nik,
                    'jenis_surat_id' => $request->input('jenis_surat_id'),
                    'bidang_tanah_id' => $request->input('bidang_tanah_id'),
                    'keperluan' => $request->input('keperluan'),
                    'status' => 'diajukan',
                ]);
                $p->update(['no_registrasi' => sprintf('REG-%s-%05d', now()->format('Ymd'), $p->id)]);

                foreach ($persyaratan as $i => $nama) {
                    $path = $request->file("dokumen.$i")->store("pengajuan-surat/{$p->id}");
                    $disimpan[] = $path;
                    DokumenPengajuan::create(['pengajuan_surat_id' => $p->id, 'nama_dokumen' => $nama, 'path_file' => $path]);
                }

                return $p;
            });
        } catch (\Throwable $e) {
            Storage::delete($disimpan);
            throw $e;
        }

        return response()->json([
            'message' => "Pengajuan berhasil dikirim. Nomor registrasi: {$pengajuan->no_registrasi}.",
        ]);
    }

    public function show(Request $request, PengajuanSurat $pengajuan)
    {
        abort_unless($pengajuan->nik_pemohon === $request->user()->nik, 403, 'Pengajuan ini bukan milik Anda.');

        $pengajuan->load(['jenisSurat:id,nama', 'bidangTanah:id,no_persil,luas_m2', 'dokumen', 'verifikator:id,name', 'penyetuju:id,name', 'penolak:id,name']);

        return view('pengajuan.show', ['p' => $pengajuan, 'peran' => 'warga']);
    }
}

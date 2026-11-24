<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatatanPengajuanRequest;
use App\Models\PengajuanSurat;
use App\Services\PenerbitanSurat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/** Alur pengajuan surat sisi petugas: admin (verifikasi) dan kepala desa (persetujuan). */
class PengajuanSuratController extends Controller
{
    public function index(Request $request)
    {
        return view('pengajuan.index', [
            'peran' => $request->user()->role,
            'status' => PengajuanSurat::STATUS,
            // Kades langsung melihat antrean yang menunggu persetujuannya.
            'statusAwal' => $request->user()->isKades() ? 'diverifikasi' : 'diajukan',
        ]);
    }

    public function data(Request $request)
    {
        $peran = $request->user()->role;

        $query = PengajuanSurat::query()
            ->select(['id', 'no_registrasi', 'nik_pemohon', 'jenis_surat_id', 'bidang_tanah_id', 'status', 'nomor_surat', 'created_at'])
            ->with(['pemohon:id,nik,nama', 'jenisSurat:id,nama', 'bidangTanah:id,no_persil']);

        if ($peran === 'kades') {
            // Kades tidak perlu melihat pengajuan yang belum diverifikasi admin.
            $query->where('status', '!=', 'diajukan');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request) {
                $kata = trim((string) $request->input('search.value'));
                if ($kata !== '') {
                    $q->where(fn ($w) => $w->where('no_registrasi', 'like', $kata.'%')
                        ->orWhere('nik_pemohon', 'like', $kata.'%')
                        ->orWhere('nomor_surat', 'like', $kata.'%'));
                }
            })
            ->addIndexColumn()
            ->editColumn('created_at', fn (PengajuanSurat $p) => $p->created_at->format('d-m-Y H:i'))
            ->addColumn('pemohon', fn (PengajuanSurat $p) => e($p->pemohon?->nama ?? '-').'<br><small class="text-muted">'.e($p->nik_pemohon).'</small>')
            ->addColumn('jenis', fn (PengajuanSurat $p) => e($p->jenisSurat?->nama ?? '-'))
            ->addColumn('persil', fn (PengajuanSurat $p) => e($p->bidangTanah?->no_persil ?? '-'))
            ->editColumn('status', fn (PengajuanSurat $p) => $p->statusBadge())
            ->addColumn('aksi', fn (PengajuanSurat $p) => '<a href="'.route("$peran.pengajuan.show", $p).'" class="btn btn-sm btn-info text-white" title="Detail"><i class="bi bi-eye"></i></a>')
            ->rawColumns(['pemohon', 'status', 'aksi'])
            ->toJson();
    }

    public function show(Request $request, PengajuanSurat $pengajuan)
    {
        $pengajuan->load([
            'pemohon:id,nik,nama,alamat', 'jenisSurat:id,nama', 'bidangTanah:id,no_persil,luas_m2,status_hak',
            'dokumen', 'verifikator:id,name', 'penyetuju:id,name', 'penolak:id,name',
        ]);

        return view('pengajuan.show', ['p' => $pengajuan, 'peran' => $request->user()->role]);
    }

    /** Admin: diajukan -> diverifikasi. */
    public function verifikasi(Request $request, PengajuanSurat $pengajuan): JsonResponse
    {
        if ($resp = $this->wajibStatus($pengajuan, 'diajukan')) {
            return $resp;
        }

        $pengajuan->update(['status' => 'diverifikasi', 'verified_by' => $request->user()->id, 'verified_at' => now(), 'catatan' => null]);

        return response()->json(['message' => 'Pengajuan diverifikasi dan diteruskan ke Kepala Desa.']);
    }

    /** Admin menolak pengajuan "diajukan"; Kades menolak pengajuan "diverifikasi". */
    public function tolak(CatatanPengajuanRequest $request, PengajuanSurat $pengajuan): JsonResponse
    {
        $perlu = $request->user()->isAdmin() ? 'diajukan' : 'diverifikasi';

        if ($resp = $this->wajibStatus($pengajuan, $perlu)) {
            return $resp;
        }

        $pengajuan->update([
            'status' => 'ditolak', 'catatan' => $request->validated('catatan'),
            'rejected_by' => $request->user()->id, 'rejected_at' => now(),
        ]);

        return response()->json(['message' => 'Pengajuan ditolak. Alasan telah disimpan.']);
    }

    /** Kades: diverifikasi -> disetujui (nomor surat & kode validasi otomatis). */
    public function setujui(Request $request, PengajuanSurat $pengajuan, PenerbitanSurat $penerbitan): JsonResponse
    {
        $p = $penerbitan->setujui($pengajuan, $request->user());

        return response()->json(['message' => "Pengajuan disetujui. Nomor surat: {$p->nomor_surat}."]);
    }

    /** Admin: disetujui -> selesai (surat sudah diserahkan ke pemohon). */
    public function selesai(PengajuanSurat $pengajuan): JsonResponse
    {
        if ($resp = $this->wajibStatus($pengajuan, 'disetujui')) {
            return $resp;
        }

        $pengajuan->update(['status' => 'selesai', 'completed_at' => now()]);

        return response()->json(['message' => 'Pengajuan ditandai selesai.']);
    }

    private function wajibStatus(PengajuanSurat $p, string $status): ?JsonResponse
    {
        return $p->status === $status ? null : response()->json([
            'message' => 'Status pengajuan sudah berubah ("'.$p->statusLabel().'"). Muat ulang halaman.',
        ], 422);
    }
}

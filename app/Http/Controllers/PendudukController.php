<?php

namespace App\Http\Controllers;

use App\Http\Requests\PendudukRequest;
use App\Models\Penduduk;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PendudukController extends Controller
{
    public function index()
    {
        return view('penduduk.index', [
            'wilayah' => Wilayah::options(),
            'jenisKelamin' => Penduduk::JENIS_KELAMIN,
            'agama' => Penduduk::AGAMA,
            'statusPerkawinan' => Penduduk::STATUS_PERKAWINAN,
            'hubungan' => Penduduk::HUBUNGAN_KELUARGA,
            'status' => Penduduk::STATUS,
        ]);
    }

    public function data(Request $request)
    {
        $query = Penduduk::query()
            ->select(['id', 'nik', 'no_kk', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'wilayah_id', 'status'])
            ->with('wilayah:id,nama_dusun,rt,rw');

        if ($request->filled('wilayah_id')) {
            $query->where('wilayah_id', $request->input('wilayah_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('jenis_kelamin')) {
            $query->where('jenis_kelamin', $request->input('jenis_kelamin'));
        }

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request) {
                $kata = trim((string) $request->input('search.value'));
                if ($kata === '') {
                    return;
                }
                if (ctype_digit($kata)) {
                    // NIK / No. KK: pencarian prefix agar memakai index.
                    $q->where(fn ($w) => $w->where('nik', 'like', $kata.'%')->orWhere('no_kk', 'like', $kata.'%'));
                } else {
                    $q->where('nama', 'like', '%'.$kata.'%');
                }
            })
            ->addIndexColumn()
            ->editColumn('jenis_kelamin', fn (Penduduk $p) => Penduduk::JENIS_KELAMIN[$p->jenis_kelamin] ?? '-')
            ->editColumn('tanggal_lahir', fn (Penduduk $p) => $p->tanggal_lahir->format('d-m-Y'))
            ->addColumn('dusun', fn (Penduduk $p) => e($p->wilayah?->label ?? '-'))
            ->editColumn('status', fn (Penduduk $p) => '<span class="badge text-bg-'.match ($p->status) {
                'hidup' => 'success', 'meninggal' => 'dark', default => 'warning',
            }.'">'.e(Penduduk::STATUS[$p->status] ?? $p->status).'</span>')
            ->addColumn('aksi', fn (Penduduk $p) => view('partials.aksi', ['id' => $p->id])->render())
            ->rawColumns(['status', 'aksi'])
            ->toJson();
    }

    public function store(PendudukRequest $request): JsonResponse
    {
        Penduduk::create($request->validated());

        return response()->json(['message' => 'Data penduduk berhasil ditambahkan.']);
    }

    public function edit(Penduduk $penduduk): JsonResponse
    {
        return response()->json([
            ...$penduduk->only([
                'id', 'nik', 'no_kk', 'nama', 'jenis_kelamin', 'tempat_lahir', 'agama', 'pekerjaan',
                'status_perkawinan', 'hubungan_keluarga', 'alamat', 'wilayah_id', 'status',
            ]),
            'tanggal_lahir' => $penduduk->tanggal_lahir->toDateString(),
        ]);
    }

    public function update(PendudukRequest $request, Penduduk $penduduk): JsonResponse
    {
        $penduduk->update(collect($request->validated())->except('nik')->all());

        return response()->json(['message' => 'Data penduduk berhasil diperbarui.']);
    }

    public function destroy(Penduduk $penduduk): JsonResponse
    {
        if ($penduduk->bidangTanah()->exists()) {
            return response()->json(['message' => 'Penduduk ini masih tercatat sebagai pemilik bidang tanah, tidak dapat dihapus.'], 422);
        }

        if (\App\Models\PeralihanHak::where('nik_pemilik_lama', $penduduk->nik)->orWhere('nik_pemilik_baru', $penduduk->nik)->exists()) {
            return response()->json(['message' => 'Penduduk ini tercatat dalam riwayat peralihan hak, tidak dapat dihapus.'], 422);
        }

        if (\App\Models\PengajuanSurat::where('nik_pemohon', $penduduk->nik)->exists()) {
            return response()->json(['message' => 'Penduduk ini memiliki pengajuan surat, tidak dapat dihapus.'], 422);
        }

        $penduduk->delete();

        return response()->json(['message' => 'Data penduduk berhasil dihapus.']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LogAktivitasController extends Controller
{
    private const MODEL = [
        'Penduduk' => 'Penduduk', 'KartuKeluarga' => 'Kartu Keluarga', 'Wilayah' => 'Wilayah',
        'BidangTanah' => 'Bidang Tanah', 'PeralihanHak' => 'Peralihan Hak', 'PengajuanSurat' => 'Pengajuan Surat', 'User' => 'Akun Pengguna',
    ];

    public function index()
    {
        return view('log-aktivitas.index', ['aksi' => LogAktivitas::AKSI, 'model' => self::MODEL]);
    }

    public function data(Request $request)
    {
        $query = LogAktivitas::query()
            ->select(['id', 'user_id', 'aksi', 'model', 'model_id', 'keterangan', 'perubahan', 'ip', 'created_at'])
            ->with('user:id,name,username');

        foreach (['aksi', 'model'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->input($f));
            }
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request) {
                $kata = trim((string) $request->input('search.value'));
                if ($kata !== '') {
                    $q->where('keterangan', 'like', $kata.'%');
                }
            })
            ->addIndexColumn()
            ->editColumn('created_at', fn (LogAktivitas $l) => $l->created_at->format('d-m-Y H:i:s'))
            ->addColumn('pengguna', fn (LogAktivitas $l) => $l->user
                ? e($l->user->name).'<br><small class="text-muted">'.e($l->user->username).'</small>'
                : '<span class="text-muted">—</span>')
            ->editColumn('aksi', fn (LogAktivitas $l) => '<span class="badge text-bg-'.match ($l->aksi) {
                'tambah' => 'success', 'ubah' => 'warning', 'hapus' => 'danger', 'login_gagal' => 'danger', default => 'secondary',
            }.'">'.e(LogAktivitas::AKSI[$l->aksi] ?? $l->aksi).'</span>')
            ->addColumn('objek', fn (LogAktivitas $l) => $l->model
                ? e(self::MODEL[$l->model] ?? $l->model).($l->keterangan ? ': <strong>'.e($l->keterangan).'</strong>' : '')
                : e($l->keterangan ?? ''))
            ->addColumn('rincian', function (LogAktivitas $l) {
                if (! $l->perubahan) {
                    return '';
                }
                $teks = collect($l->perubahan)->map(fn ($v, $k) => $k.': '.(is_scalar($v) || $v === null ? (string) $v : json_encode($v)))->implode('; ');

                return '<span title="'.e($teks).'" class="small">'.e(mb_strimwidth($teks, 0, 70, '…')).'</span>';
            })
            ->rawColumns(['pengguna', 'aksi', 'objek', 'rincian'])
            ->toJson();
    }
}

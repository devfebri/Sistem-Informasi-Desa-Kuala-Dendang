<?php

namespace App\Models;

use App\Models\Concerns\MencatatAktivitas;
use App\Models\Concerns\MengosongkanCacheStatistik;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanSurat extends Model
{
    use HasFactory, MencatatAktivitas, MengosongkanCacheStatistik;

    protected $table = 'pengajuan_surat';

    public const STATUS = [
        'diajukan' => 'Diajukan',
        'diverifikasi' => 'Diverifikasi Admin',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
        'selesai' => 'Selesai',
    ];

    private const WARNA = [
        'diajukan' => 'secondary', 'diverifikasi' => 'info', 'disetujui' => 'success',
        'ditolak' => 'danger', 'selesai' => 'primary',
    ];

    /** Pengajuan yang masih berjalan (mencegah pengajuan ganda). */
    public const AKTIF = ['diajukan', 'diverifikasi'];

    protected $fillable = [
        'no_registrasi', 'nik_pemohon', 'jenis_surat_id', 'bidang_tanah_id', 'keperluan', 'status', 'catatan',
        'verified_by', 'verified_at', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'completed_at',
        'tahun', 'no_urut', 'nomor_surat', 'kode_validasi',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime', 'approved_at' => 'datetime',
            'rejected_at' => 'datetime', 'completed_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return '<span class="badge text-bg-'.(self::WARNA[$this->status] ?? 'secondary').'">'.e($this->statusLabel()).'</span>';
    }

    public function suratTerbit(): bool
    {
        return in_array($this->status, ['disetujui', 'selesai'], true) && $this->nomor_surat !== null;
    }

    public function pemohon()
    {
        return $this->belongsTo(Penduduk::class, 'nik_pemohon', 'nik');
    }

    public function jenisSurat()
    {
        return $this->belongsTo(JenisSurat::class);
    }

    public function bidangTanah()
    {
        return $this->belongsTo(BidangTanah::class);
    }

    public function dokumen()
    {
        return $this->hasMany(DokumenPengajuan::class);
    }

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function penyetuju()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function penolak()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}

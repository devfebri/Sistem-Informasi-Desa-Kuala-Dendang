<?php

namespace App\Models;

use App\Models\Concerns\MencatatAktivitas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeralihanHak extends Model
{
    use HasFactory, MencatatAktivitas;

    protected $table = 'peralihan_hak';

    public const JENIS = [
        'jual_beli' => 'Jual Beli',
        'waris' => 'Waris',
        'hibah' => 'Hibah',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'bidang_tanah_id', 'nik_pemilik_lama', 'nik_pemilik_baru', 'jenis', 'tanggal',
        'no_akta', 'dokumen', 'keterangan', 'created_by',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function jenisLabel(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    public function bidangTanah()
    {
        return $this->belongsTo(BidangTanah::class);
    }

    public function pemilikLama()
    {
        return $this->belongsTo(Penduduk::class, 'nik_pemilik_lama', 'nik');
    }

    public function pemilikBaru()
    {
        return $this->belongsTo(Penduduk::class, 'nik_pemilik_baru', 'nik');
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

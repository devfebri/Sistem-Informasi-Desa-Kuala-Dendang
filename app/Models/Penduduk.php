<?php

namespace App\Models;

use App\Models\Concerns\MencatatAktivitas;
use App\Models\Concerns\MengosongkanCacheStatistik;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penduduk extends Model
{
    use HasFactory, MencatatAktivitas, MengosongkanCacheStatistik;

    protected $table = 'penduduk';

    public const JENIS_KELAMIN = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
    public const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
    public const STATUS_PERKAWINAN = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
    public const HUBUNGAN_KELUARGA = ['Kepala Keluarga', 'Istri', 'Anak', 'Orang Tua', 'Famili Lain', 'Lainnya'];
    public const STATUS = ['hidup' => 'Hidup', 'meninggal' => 'Meninggal', 'pindah' => 'Pindah'];

    protected $fillable = [
        'nik', 'no_kk', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'pekerjaan',
        'status_perkawinan', 'hubungan_keluarga', 'alamat', 'wilayah_id', 'status',
    ];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }

    public function kartuKeluarga()
    {
        return $this->belongsTo(KartuKeluarga::class, 'no_kk', 'no_kk');
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function bidangTanah()
    {
        return $this->hasMany(BidangTanah::class, 'nik_pemilik', 'nik');
    }
}

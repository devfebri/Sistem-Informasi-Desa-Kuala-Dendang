<?php

namespace App\Models;

use App\Models\Concerns\MencatatAktivitas;
use App\Models\Concerns\MengosongkanCacheStatistik;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KartuKeluarga extends Model
{
    use HasFactory, MencatatAktivitas, MengosongkanCacheStatistik;

    protected $table = 'kartu_keluarga';

    protected $fillable = ['no_kk', 'nik_kepala_keluarga', 'alamat', 'wilayah_id'];

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function kepala()
    {
        return $this->hasOne(Penduduk::class, 'nik', 'nik_kepala_keluarga');
    }

    public function anggota()
    {
        return $this->hasMany(Penduduk::class, 'no_kk', 'no_kk');
    }
}

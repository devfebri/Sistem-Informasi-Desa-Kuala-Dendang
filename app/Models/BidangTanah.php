<?php

namespace App\Models;

use App\Models\Concerns\MencatatAktivitas;
use App\Models\Concerns\MengosongkanCacheStatistik;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BidangTanah extends Model
{
    use HasFactory, MencatatAktivitas, MengosongkanCacheStatistik;

    protected $table = 'bidang_tanah';

    public const STATUS_HAK = ['SHM', 'HGB', 'SKT', 'Girik', 'Letter C', 'Belum bersertifikat'];
    public const PENGGUNAAN = ['pemukiman', 'sawah', 'kebun', 'ladang', 'lainnya'];

    protected $fillable = [
        'no_persil', 'nik_pemilik', 'luas_m2', 'luas_geometri_m2', 'status_hak', 'no_sertifikat',
        'penggunaan_lahan', 'batas_utara', 'batas_selatan', 'batas_timur', 'batas_barat', 'wilayah_id',
        'geojson', 'min_lng', 'min_lat', 'max_lng', 'max_lat', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'geojson' => 'array',
            'luas_m2' => 'float',
            'luas_geometri_m2' => 'float',
        ];
    }

    public static function newFactory()
    {
        return \Database\Factories\BidangTanahFactory::new();
    }

    /** Batasi ke persil yang bounding box-nya beririsan dengan "minLng,minLat,maxLng,maxLat". */
    public function scopeDalamBbox($query, ?string $bbox)
    {
        $p = $bbox ? array_map('floatval', explode(',', $bbox)) : [];

        return count($p) === 4
            ? $query->where('max_lng', '>=', $p[0])->where('min_lng', '<=', $p[2])
                ->where('max_lat', '>=', $p[1])->where('min_lat', '<=', $p[3])
            : $query;
    }

    public function pemilik()
    {
        return $this->belongsTo(Penduduk::class, 'nik_pemilik', 'nik');
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function peralihanHak()
    {
        return $this->hasMany(PeralihanHak::class);
    }
}

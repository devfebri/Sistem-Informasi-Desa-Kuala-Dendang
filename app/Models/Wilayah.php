<?php

namespace App\Models;

use App\Models\Concerns\MencatatAktivitas;
use App\Models\Concerns\MengosongkanCacheStatistik;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Wilayah extends Model
{
    use HasFactory, MencatatAktivitas, MengosongkanCacheStatistik;

    protected $table = 'wilayah';

    protected $fillable = ['nama_dusun', 'rt', 'rw', 'geojson'];

    protected function casts(): array
    {
        return ['geojson' => 'array'];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('wilayah.options');
        static::saved($flush);
        static::deleted($flush);
    }

    public function kartuKeluarga()
    {
        return $this->hasMany(KartuKeluarga::class);
    }

    public function penduduk()
    {
        return $this->hasMany(Penduduk::class);
    }

    public function getLabelAttribute(): string
    {
        return "{$this->nama_dusun} RT {$this->rt}/RW {$this->rw}";
    }

    /** Data master kecil, di-cache 10 menit. Hasil: [id => label]. */
    public static function options(): array
    {
        return Cache::remember('wilayah.options', 600, fn () => static::query()
            ->orderBy('nama_dusun')->orderBy('rt')
            ->get(['id', 'nama_dusun', 'rt', 'rw'])
            ->mapWithKeys(fn ($w) => [$w->id => $w->label])->all());
    }
}

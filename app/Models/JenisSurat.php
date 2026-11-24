<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class JenisSurat extends Model
{
    protected $table = 'jenis_surat';

    protected $fillable = ['kode', 'nama', 'template_view', 'persyaratan', 'is_active'];

    protected function casts(): array
    {
        return ['persyaratan' => 'array', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('jenis_surat.aktif');
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Master kecil, di-cache 10 menit. Yang disimpan di cache adalah array biasa (bukan objek Eloquent):
     * cache store berbasis serialisasi (database/file/redis) menolak mem-unserialize model.
     *
     * @return \Illuminate\Support\Collection<int, object{id:int, kode:string, nama:string, persyaratan:array}>
     */
    public static function aktif(): \Illuminate\Support\Collection
    {
        $baris = Cache::remember('jenis_surat.aktif', 600, fn () => static::query()
            ->where('is_active', true)->orderBy('id')->get(['id', 'kode', 'nama', 'persyaratan'])
            ->map(fn ($j) => ['id' => $j->id, 'kode' => $j->kode, 'nama' => $j->nama, 'persyaratan' => $j->persyaratan ?? []])
            ->all());

        return collect($baris)->map(fn (array $r) => (object) $r);
    }
}

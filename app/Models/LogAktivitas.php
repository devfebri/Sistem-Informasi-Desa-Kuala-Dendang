<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class LogAktivitas extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'log_aktivitas';

    public const AKSI = [
        'tambah' => 'Tambah', 'ubah' => 'Ubah', 'hapus' => 'Hapus',
        'login' => 'Login', 'logout' => 'Logout', 'login_gagal' => 'Login gagal',
    ];

    /** Atribut yang tidak pernah dicatat ke log (sensitif / berukuran besar / turunan). */
    private const DIABAIKAN = ['remember_token', 'updated_at', 'created_at', 'min_lng', 'min_lat', 'max_lng', 'max_lat'];

    protected $fillable = ['user_id', 'aksi', 'model', 'model_id', 'keterangan', 'perubahan', 'ip'];

    protected function casts(): array
    {
        return ['perubahan' => 'array', 'created_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Catat perubahan model oleh pengguna yang sedang login (dilewati bila tanpa login, mis. seeder). */
    public static function catatModel(string $aksi, Model $model, array $perubahan = []): void
    {
        if (! auth()->check()) {
            return;
        }

        static::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'model' => class_basename($model),
            'model_id' => $model->getKey(),
            'keterangan' => static::label($model),
            'perubahan' => $perubahan ? static::bersihkan($perubahan) : null,
            'ip' => request()->ip(),
        ]);
    }

    /** Catat peristiwa non-model (login, logout, login gagal). */
    public static function catatPeristiwa(string $aksi, ?int $userId, ?string $keterangan = null): void
    {
        static::create([
            'user_id' => $userId, 'aksi' => $aksi, 'keterangan' => $keterangan, 'ip' => request()->ip(),
        ]);
    }

    private static function label(Model $m): ?string
    {
        foreach (['no_persil', 'no_registrasi', 'username', 'nama', 'no_kk', 'nama_dusun', 'nomor_surat'] as $k) {
            if (filled($m->getAttribute($k))) {
                return mb_substr((string) $m->getAttribute($k), 0, 255);
            }
        }

        return null;
    }

    private static function bersihkan(array $perubahan): array
    {
        $hasil = [];
        foreach (Arr::except($perubahan, self::DIABAIKAN) as $k => $v) {
            $hasil[$k] = match (true) {
                $k === 'geojson' => '[geometri diubah]',
                $k === 'password' => '[diganti]',
                is_string($v) => mb_substr($v, 0, 120),
                default => $v,
            };
        }

        return $hasil;
    }
}

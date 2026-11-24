<?php

namespace App\Models\Concerns;

use App\Models\LogAktivitas;

/** Catat tambah/ubah/hapus pada model ini ke log aktivitas (oleh pengguna yang sedang login). */
trait MencatatAktivitas
{
    protected static function bootMencatatAktivitas(): void
    {
        static::created(fn ($m) => LogAktivitas::catatModel('tambah', $m));

        static::updated(function ($m) {
            $perubahan = $m->getChanges();
            // Bila hanya updated_at/remember_token yang berubah (mis. saat login), tidak dicatat.
            if (array_diff(array_keys($perubahan), ['updated_at', 'remember_token'])) {
                LogAktivitas::catatModel('ubah', $m, $perubahan);
            }
        });

        static::deleted(fn ($m) => LogAktivitas::catatModel('hapus', $m));
    }
}

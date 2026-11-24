<?php

namespace App\Models\Concerns;

use App\Services\StatistikDesa;

/** Hapus cache statistik dashboard setiap kali data sumbernya berubah. */
trait MengosongkanCacheStatistik
{
    protected static function bootMengosongkanCacheStatistik(): void
    {
        $flush = fn () => StatistikDesa::lupakan();

        static::saved($flush);
        static::deleted($flush);
    }
}

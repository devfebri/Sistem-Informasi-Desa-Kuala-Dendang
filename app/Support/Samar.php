<?php

namespace App\Support;

/** Penyamaran data pribadi untuk tampilan publik. */
class Samar
{
    /** "Budi Santoso" -> "B*** S******" (huruf pertama tiap kata saja). */
    public static function nama(?string $nama): string
    {
        return collect(preg_split('/\s+/', trim((string) $nama)))->filter()
            ->map(fn ($k) => mb_substr($k, 0, 1).str_repeat('*', max(mb_strlen($k) - 1, 2)))
            ->implode(' ');
    }
}

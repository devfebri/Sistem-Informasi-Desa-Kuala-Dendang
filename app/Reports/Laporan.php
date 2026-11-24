<?php

namespace App\Reports;

use Illuminate\Database\Eloquent\Builder;

/**
 * Definisi satu laporan. Kelas yang sama dipakai untuk pratinjau tabel (AJAX), PDF, dan Excel,
 * sehingga ketiganya selalu konsisten.
 */
abstract class Laporan
{
    public const DAFTAR = [
        'buku-tanah' => BukuTanah::class,
        'rekap-peralihan' => RekapPeralihan::class,
        'rekap-surat' => RekapSurat::class,
    ];

    public static function dari(string $slug): static
    {
        $kelas = self::DAFTAR[$slug] ?? abort(404);

        return new $kelas;
    }

    abstract public function judul(): string;

    /** @return array<int, string> judul kolom */
    abstract public function kolom(): array;

    /**
     * Definisi filter untuk form: ['name', 'label', 'type' => select|date, 'options' => [...], 'default' => ...].
     *
     * @return array<int, array<string, mixed>>
     */
    abstract public function filter(): array;

    /** Aturan validasi untuk filter. */
    abstract public function aturan(): array;

    abstract public function query(array $f): Builder;

    /** @return array<int, scalar|null> nilai tiap kolom (teks polos, tanpa HTML) */
    abstract public function baris($model): array;

    /** Ringkasan di atas tabel PDF: label => nilai. */
    public function ringkasan(array $f): array
    {
        return [];
    }

    /** Teks periode/filter yang tampil pada judul PDF. */
    public function deskripsiFilter(array $f): string
    {
        return '';
    }

    /** Indeks kolom yang nilainya dikelompokkan (sel berulang dikosongkan di PDF), atau null. */
    public function kelompok(): ?int
    {
        return null;
    }

    /** Lebar relatif kolom pada PDF (persen). Kosong = rata. */
    public function lebarKolom(): array
    {
        return [];
    }

    public function berkas(string $ekstensi): string
    {
        return str_replace(' ', '-', strtolower($this->judul())).'-'.now()->format('Ymd').'.'.$ekstensi;
    }

    /** Nilai default filter (mis. periode tahun berjalan). */
    public function nilaiAwal(): array
    {
        return collect($this->filter())->mapWithKeys(fn ($f) => [$f['name'] => $f['default'] ?? null])->all();
    }
}

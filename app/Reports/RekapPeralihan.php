<?php

namespace App\Reports;

use App\Models\PeralihanHak;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class RekapPeralihan extends Laporan
{
    public function judul(): string
    {
        return 'Rekap Peralihan Hak';
    }

    public function kolom(): array
    {
        return ['Tanggal', 'No. Persil', 'Pemilik Lama', 'NIK Lama', 'Pemilik Baru', 'NIK Baru', 'Jenis', 'No. Akta', 'Keterangan'];
    }

    public function lebarKolom(): array
    {
        return [8, 8, 15, 11, 15, 11, 8, 10, 14];
    }

    public function filter(): array
    {
        return [
            ['name' => 'dari', 'label' => 'Dari tanggal', 'type' => 'date', 'default' => now()->startOfYear()->toDateString()],
            ['name' => 'sampai', 'label' => 'Sampai tanggal', 'type' => 'date', 'default' => now()->toDateString()],
            ['name' => 'jenis', 'label' => 'Jenis peralihan', 'type' => 'select', 'options' => PeralihanHak::JENIS],
        ];
    }

    public function aturan(): array
    {
        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'jenis' => ['nullable', Rule::in(array_keys(PeralihanHak::JENIS))],
        ];
    }

    public function query(array $f): Builder
    {
        return PeralihanHak::query()
            ->with(['bidangTanah:id,no_persil', 'pemilikLama:id,nik,nama', 'pemilikBaru:id,nik,nama'])
            ->when($f['dari'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
            ->when($f['sampai'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))
            ->when($f['jenis'] ?? null, fn ($q, $v) => $q->where('jenis', $v))
            ->orderByDesc('tanggal')->orderByDesc('id');
    }

    public function baris($m): array
    {
        return [
            $m->tanggal->format('d-m-Y'),
            $m->bidangTanah?->no_persil ?? '-',
            $m->pemilikLama?->nama ?? '-',
            $m->nik_pemilik_lama,
            $m->pemilikBaru?->nama ?? '-',
            $m->nik_pemilik_baru,
            $m->jenisLabel(),
            $m->no_akta ?: '-',
            $m->keterangan ?: '-',
        ];
    }

    public function ringkasan(array $f): array
    {
        $per = $this->query($f)->reorder()->toBase()->selectRaw('jenis, count(*) as n')->groupBy('jenis')->pluck('n', 'jenis');
        $hasil = ['Total peralihan' => number_format($per->sum(), 0, ',', '.')];
        foreach (PeralihanHak::JENIS as $k => $label) {
            $hasil[$label] = (int) ($per[$k] ?? 0);
        }

        return $hasil;
    }

    public function deskripsiFilter(array $f): string
    {
        $tgl = fn ($v) => Carbon::parse($v)->translatedFormat('d F Y');
        $periode = match (true) {
            ! empty($f['dari']) && ! empty($f['sampai']) => 'Periode '.$tgl($f['dari']).' s.d. '.$tgl($f['sampai']),
            ! empty($f['dari']) => 'Sejak '.$tgl($f['dari']),
            ! empty($f['sampai']) => 'Sampai '.$tgl($f['sampai']),
            default => 'Seluruh periode',
        };

        return $periode.(! empty($f['jenis']) ? ' · Jenis: '.PeralihanHak::JENIS[$f['jenis']] : '');
    }
}

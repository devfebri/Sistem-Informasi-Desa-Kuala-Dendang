<?php

namespace App\Reports;

use App\Models\JenisSurat;
use App\Models\PengajuanSurat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class RekapSurat extends Laporan
{
    public function judul(): string
    {
        return 'Rekap Pengajuan Surat';
    }

    public function kolom(): array
    {
        return ['Tgl. Diajukan', 'No. Registrasi', 'Pemohon', 'NIK', 'Jenis Surat', 'No. Persil', 'Status', 'Nomor Surat', 'Tgl. Terbit'];
    }

    public function lebarKolom(): array
    {
        return [8, 11, 14, 11, 14, 7, 9, 14, 8];
    }

    public function filter(): array
    {
        return [
            ['name' => 'dari', 'label' => 'Dari tanggal', 'type' => 'date', 'default' => now()->startOfYear()->toDateString()],
            ['name' => 'sampai', 'label' => 'Sampai tanggal', 'type' => 'date', 'default' => now()->toDateString()],
            ['name' => 'jenis_surat_id', 'label' => 'Jenis surat', 'type' => 'select', 'options' => JenisSurat::aktif()->pluck('nama', 'id')->all()],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => PengajuanSurat::STATUS],
        ];
    }

    public function aturan(): array
    {
        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'jenis_surat_id' => ['nullable', Rule::exists('jenis_surat', 'id')],
            'status' => ['nullable', Rule::in(array_keys(PengajuanSurat::STATUS))],
        ];
    }

    public function query(array $f): Builder
    {
        return PengajuanSurat::query()
            ->with(['pemohon:id,nik,nama', 'jenisSurat:id,nama', 'bidangTanah:id,no_persil'])
            ->when($f['dari'] ?? null, fn ($q, $v) => $q->whereDate('pengajuan_surat.created_at', '>=', $v))
            ->when($f['sampai'] ?? null, fn ($q, $v) => $q->whereDate('pengajuan_surat.created_at', '<=', $v))
            ->when($f['jenis_surat_id'] ?? null, fn ($q, $v) => $q->where('jenis_surat_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('pengajuan_surat.created_at')->orderByDesc('pengajuan_surat.id');
    }

    public function baris($m): array
    {
        return [
            $m->created_at->format('d-m-Y'),
            $m->no_registrasi,
            $m->pemohon?->nama ?? '-',
            $m->nik_pemohon,
            $m->jenisSurat?->nama ?? '-',
            $m->bidangTanah?->no_persil ?? '-',
            $m->statusLabel(),
            $m->nomor_surat ?: '-',
            $m->approved_at?->format('d-m-Y') ?? '-',
        ];
    }

    public function ringkasan(array $f): array
    {
        $per = $this->query($f)->reorder()->toBase()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $hasil = ['Total pengajuan' => number_format($per->sum(), 0, ',', '.')];
        foreach (PengajuanSurat::STATUS as $k => $label) {
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
        $tambah = [];
        if (! empty($f['jenis_surat_id'])) {
            $tambah[] = 'Jenis: '.(JenisSurat::aktif()->firstWhere('id', $f['jenis_surat_id'])?->nama ?? '-');
        }
        if (! empty($f['status'])) {
            $tambah[] = 'Status: '.PengajuanSurat::STATUS[$f['status']];
        }

        return implode(' · ', array_merge([$periode], $tambah));
    }
}

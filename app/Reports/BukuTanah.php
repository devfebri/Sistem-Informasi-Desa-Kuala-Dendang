<?php

namespace App\Reports;

use App\Models\BidangTanah;
use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/** Buku Tanah Desa: daftar persil dikelompokkan per pemilik. */
class BukuTanah extends Laporan
{
    public function judul(): string
    {
        return 'Buku Tanah Desa';
    }

    public function kolom(): array
    {
        return ['Pemilik', 'NIK', 'No. Persil', 'Luas (m²)', 'Status Hak', 'No. Sertifikat', 'Penggunaan', 'Dusun/RT', 'Batas U / S / T / B'];
    }

    public function lebarKolom(): array
    {
        return [14, 11, 8, 7, 8, 10, 8, 14, 20];
    }

    public function kelompok(): ?int
    {
        return 0;
    }

    public function filter(): array
    {
        return [
            ['name' => 'wilayah_id', 'label' => 'Dusun / RT', 'type' => 'select', 'options' => Wilayah::options()],
            ['name' => 'status_hak', 'label' => 'Status hak', 'type' => 'select', 'options' => array_combine(BidangTanah::STATUS_HAK, BidangTanah::STATUS_HAK)],
            ['name' => 'penggunaan_lahan', 'label' => 'Penggunaan lahan', 'type' => 'select', 'options' => array_combine(BidangTanah::PENGGUNAAN, array_map('ucfirst', BidangTanah::PENGGUNAAN))],
        ];
    }

    public function aturan(): array
    {
        return [
            'wilayah_id' => ['nullable', Rule::exists('wilayah', 'id')],
            'status_hak' => ['nullable', Rule::in(BidangTanah::STATUS_HAK)],
            'penggunaan_lahan' => ['nullable', Rule::in(BidangTanah::PENGGUNAAN)],
        ];
    }

    public function query(array $f): Builder
    {
        return BidangTanah::query()
            ->join('penduduk', 'penduduk.nik', '=', 'bidang_tanah.nik_pemilik')
            ->select('bidang_tanah.*', 'penduduk.nama as nama_pemilik')
            ->with('wilayah:id,nama_dusun,rt,rw')
            ->when($f['wilayah_id'] ?? null, fn ($q, $v) => $q->where('bidang_tanah.wilayah_id', $v))
            ->when($f['status_hak'] ?? null, fn ($q, $v) => $q->where('bidang_tanah.status_hak', $v))
            ->when($f['penggunaan_lahan'] ?? null, fn ($q, $v) => $q->where('bidang_tanah.penggunaan_lahan', $v))
            ->orderBy('penduduk.nama')->orderBy('bidang_tanah.nik_pemilik')->orderBy('bidang_tanah.no_persil');
    }

    public function baris($m): array
    {
        return [
            $m->nama_pemilik,
            $m->nik_pemilik,
            $m->no_persil,
            number_format($m->luas_m2, 0, ',', '.'),
            $m->status_hak,
            $m->no_sertifikat ?: '-',
            ucfirst($m->penggunaan_lahan),
            $m->wilayah?->label ?? '-',
            implode(' / ', [$m->batas_utara ?: '-', $m->batas_selatan ?: '-', $m->batas_timur ?: '-', $m->batas_barat ?: '-']),
        ];
    }

    public function ringkasan(array $f): array
    {
        $a = $this->query($f)->reorder()->toBase()->cloneWithout(['columns'])->selectRaw('count(*) as n, coalesce(sum(bidang_tanah.luas_m2), 0) as luas, count(distinct bidang_tanah.nik_pemilik) as pemilik')->first();

        return [
            'Jumlah persil' => number_format($a->n, 0, ',', '.'),
            'Jumlah pemilik' => number_format($a->pemilik, 0, ',', '.'),
            'Total luas' => number_format($a->luas, 0, ',', '.').' m² ('.number_format($a->luas / 10000, 2, ',', '.').' ha)',
        ];
    }

    public function deskripsiFilter(array $f): string
    {
        $bagian = [];
        if (! empty($f['wilayah_id'])) {
            $bagian[] = 'Wilayah: '.(Wilayah::options()[$f['wilayah_id']] ?? '-');
        }
        if (! empty($f['status_hak'])) {
            $bagian[] = 'Status hak: '.$f['status_hak'];
        }
        if (! empty($f['penggunaan_lahan'])) {
            $bagian[] = 'Penggunaan: '.ucfirst($f['penggunaan_lahan']);
        }

        return $bagian ? implode(' · ', $bagian) : 'Seluruh persil';
    }
}

<?php

namespace App\Exports;

use App\Reports\Laporan;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ekspor Excel dari definisi Laporan (query bertahap/chunk, hemat memori).
 * Semua sel ditulis sebagai teks agar NIK/No. KK 16 digit tidak berubah menjadi angka ilmiah.
 */
class LaporanExport extends StringValueBinder implements FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(private Laporan $laporan, private array $filter)
    {
    }

    public function query(): Builder
    {
        return $this->laporan->query($this->filter);
    }

    public function headings(): array
    {
        return $this->laporan->kolom();
    }

    public function map($row): array
    {
        return $this->laporan->baris($row);
    }

    public function title(): string
    {
        return mb_substr($this->laporan->judul(), 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}

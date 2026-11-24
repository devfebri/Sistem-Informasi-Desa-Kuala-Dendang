<?php

namespace App\Services;

use App\Models\PengajuanSurat;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PenerbitanSurat
{
    private const ROMAWI = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /**
     * Setujui pengajuan: nomor surat urut per tahun + kode validasi (untuk QR).
     * Dalam transaksi dengan penguncian agar nomor tidak ganda.
     */
    public function setujui(PengajuanSurat $pengajuan, User $kades): PengajuanSurat
    {
        return DB::transaction(function () use ($pengajuan, $kades) {
            $p = PengajuanSurat::query()->lockForUpdate()->with('jenisSurat:id,kode')->findOrFail($pengajuan->id);

            if ($p->status !== 'diverifikasi') {
                throw new HttpResponseException(response()->json([
                    'message' => 'Pengajuan ini tidak berstatus "Diverifikasi" sehingga tidak dapat disetujui.',
                ], 422));
            }

            $now = now();
            $tahun = (int) $now->year;
            $urut = ((int) PengajuanSurat::query()->where('tahun', $tahun)->lockForUpdate()->max('no_urut')) + 1;

            $p->update([
                'status' => 'disetujui',
                'approved_by' => $kades->id,
                'approved_at' => $now,
                'tahun' => $tahun,
                'no_urut' => $urut,
                'nomor_surat' => $this->formatNomor($urut, $p->jenisSurat->kode, $now->month, $tahun),
                'kode_validasi' => $this->kodeUnik(),
                'catatan' => null,
            ]);

            return $p;
        });
    }

    public function formatNomor(int $urut, string $kodeJenis, int $bulan, int $tahun): string
    {
        return sprintf('%03d/%s/%s/%s/%d', $urut, $kodeJenis, config('desa.kode_surat', 'DS'), self::ROMAWI[$bulan], $tahun);
    }

    private function kodeUnik(): string
    {
        do {
            $kode = strtoupper(Str::random(12));
        } while (PengajuanSurat::where('kode_validasi', $kode)->exists());

        return $kode;
    }
}

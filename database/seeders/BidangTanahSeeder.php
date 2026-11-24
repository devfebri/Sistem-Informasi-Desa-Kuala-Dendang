<?php

namespace Database\Seeders;

use App\Models\BidangTanah;
use App\Models\Penduduk;
use App\Models\Wilayah;
use App\Support\Geo;
use Illuminate\Database\Seeder;

/**
 * ±32 bidang tanah contoh + batas dusun, tersusun rapi di sekitar koordinat pusat di config/desa.php.
 * Ganti koordinat pusat, hapus data bidang_tanah, lalu seed ulang agar polygon pindah ke lokasi desa.
 */
class BidangTanahSeeder extends Seeder
{
    private const PARCEL_W = 28;   // meter
    private const PARCEL_H = 32;
    private const PITCH_X = 34;
    private const PITCH_Y = 38;
    private const KOLOM = 2;
    private const BARIS = 4;

    public function run(): void
    {
        if (BidangTanah::query()->exists()) {
            return;
        }

        [$lat0, $lng0] = config('desa.peta.pusat');
        $mLat = 1 / 111320;
        $mLng = 1 / (111320 * cos(deg2rad($lat0)));

        $titik = fn (float $east, float $north) => [round($lng0 + $east * $mLng, 7), round($lat0 + $north * $mLat, 7)];
        $kotak = fn (float $x1, float $y1, float $x2, float $y2) => [[
            $titik($x1, $y1), $titik($x2, $y1), $titik($x2, $y2), $titik($x1, $y2), $titik($x1, $y1),
        ]];

        $dusun = Wilayah::query()->orderBy('id')->pluck('nama_dusun')->unique()->values();
        $pemilik = Penduduk::query()->where('nik', 'like', '150501%')->where('hubungan_keluarga', 'Kepala Keluarga')
            ->orderBy('id')->pluck('nik')->values();

        if ($pemilik->isEmpty()) {
            return;
        }

        $statusBobot = ['SHM' => 8, 'SKT' => 10, 'Girik' => 5, 'Letter C' => 3, 'HGB' => 2, 'Belum bersertifikat' => 4];
        $guna = ['pemukiman', 'pemukiman', 'kebun', 'sawah', 'ladang', 'lainnya'];
        $no = 0;

        foreach ($dusun as $k => $namaDusun) {
            $rows = Wilayah::where('nama_dusun', $namaDusun)->orderBy('rt')->get();
            $ox = ($k % 2) * 170 - 170;
            $oy = intdiv($k, 2) * 200 - 200;

            // Batas dusun
            $luasX = self::KOLOM * self::PITCH_X;
            $luasY = self::BARIS * self::PITCH_Y;
            $batas = ['type' => 'Polygon', 'coordinates' => $kotak($ox - 10, $oy - 10, $ox + $luasX + 10, $oy + $luasY + 10)];
            Wilayah::where('nama_dusun', $namaDusun)->update(['geojson' => json_encode($batas)]);

            for ($c = 0; $c < self::KOLOM; $c++) {
                for ($r = 0; $r < self::BARIS; $r++) {
                    $w = self::PARCEL_W + random_int(-3, 3);
                    $h = self::PARCEL_H + random_int(-3, 3);
                    $x = $ox + $c * self::PITCH_X;
                    $y = $oy + $r * self::PITCH_Y;
                    $geom = ['type' => 'Polygon', 'coordinates' => $kotak($x, $y, $x + $w, $y + $h)];
                    [$minLng, $minLat, $maxLng, $maxLat] = Geo::bbox($geom);
                    $luasGeom = round(Geo::area($geom), 2);

                    $status = $this->acak($statusBobot);
                    $no++;

                    BidangTanah::create([
                        'no_persil' => sprintf('PRS-%03d', $no),
                        'nik_pemilik' => $pemilik[($no - 1) % $pemilik->count()],
                        'luas_m2' => round($luasGeom * random_int(95, 105) / 100, 0),
                        'luas_geometri_m2' => $luasGeom,
                        'status_hak' => $status,
                        'no_sertifikat' => in_array($status, ['SHM', 'HGB'], true) ? sprintf('%s-%05d', $status, 10000 + $no) : null,
                        'penggunaan_lahan' => $guna[array_rand($guna)],
                        'batas_utara' => 'Tanah milik warga / jalan desa',
                        'batas_selatan' => 'Tanah milik warga',
                        'batas_timur' => 'Parit / tanah milik warga',
                        'batas_barat' => 'Jalan desa',
                        'wilayah_id' => $rows[$c % $rows->count()]->id,
                        'geojson' => $geom,
                        'min_lng' => $minLng, 'min_lat' => $minLat, 'max_lng' => $maxLng, 'max_lat' => $maxLat,
                    ]);
                }
            }
        }
    }

    private function acak(array $bobot): string
    {
        $t = random_int(1, array_sum($bobot));
        foreach ($bobot as $k => $b) {
            if (($t -= $b) <= 0) {
                return $k;
            }
        }

        return array_key_first($bobot);
    }
}

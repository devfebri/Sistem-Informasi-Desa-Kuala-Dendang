<?php

namespace Database\Factories;

use App\Models\BidangTanah;
use App\Models\Penduduk;
use App\Models\Wilayah;
use App\Support\Geo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BidangTanah> */
class BidangTanahFactory extends Factory
{
    public function definition(): array
    {
        return [
            'no_persil' => 'PRS-'.fake()->unique()->numerify('#####'),
            'nik_pemilik' => fn () => Penduduk::factory()->create()->nik,
            'luas_m2' => 1000,
            'status_hak' => 'SKT',
            'penggunaan_lahan' => 'pemukiman',
            'wilayah_id' => fn () => Wilayah::factory()->create()->id,
        ];
    }

    /** Persegi di sekitar (lng, lat), sisi ±$sisi meter. */
    public function persegi(float $lng = 103.0, float $lat = -1.0, float $sisi = 30): static
    {
        $dLat = $sisi / 111320;
        $dLng = $sisi / (111320 * cos(deg2rad($lat)));
        $geom = ['type' => 'Polygon', 'coordinates' => [[
            [$lng, $lat], [$lng + $dLng, $lat], [$lng + $dLng, $lat + $dLat], [$lng, $lat + $dLat], [$lng, $lat],
        ]]];
        [$minLng, $minLat, $maxLng, $maxLat] = Geo::bbox($geom);

        return $this->state([
            'geojson' => $geom,
            'luas_geometri_m2' => round(Geo::area($geom), 2),
            'min_lng' => $minLng, 'min_lat' => $minLat, 'max_lng' => $maxLng, 'max_lat' => $maxLat,
        ]);
    }
}

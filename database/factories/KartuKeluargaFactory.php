<?php

namespace Database\Factories;

use App\Models\KartuKeluarga;
use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KartuKeluarga> */
class KartuKeluargaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'no_kk' => fake()->unique()->numerify('################'),
            'nik_kepala_keluarga' => null,
            'alamat' => fake()->streetAddress(),
            'wilayah_id' => Wilayah::factory(),
        ];
    }
}

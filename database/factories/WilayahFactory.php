<?php

namespace Database\Factories;

use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Wilayah> */
class WilayahFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_dusun' => 'Dusun '.fake()->unique()->lexify('????'),
            'rt' => str_pad((string) fake()->numberBetween(1, 9), 3, '0', STR_PAD_LEFT),
            'rw' => str_pad((string) fake()->numberBetween(1, 3), 3, '0', STR_PAD_LEFT),
        ];
    }
}

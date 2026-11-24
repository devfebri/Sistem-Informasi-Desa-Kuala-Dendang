<?php

namespace Database\Factories;

use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Penduduk> */
class PendudukFactory extends Factory
{
    public function definition(): array
    {
        $jk = fake()->randomElement(['L', 'P']);

        return [
            'nik' => fake()->unique()->numerify('################'),
            'no_kk' => fn () => KartuKeluarga::factory()->create()->no_kk,
            'nama' => fake()->name($jk === 'L' ? 'male' : 'female'),
            'jenis_kelamin' => $jk,
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-70 years', '-1 year')->format('Y-m-d'),
            'agama' => 'Islam',
            'pekerjaan' => fake()->randomElement(['Petani', 'Nelayan', 'Wiraswasta', 'Buruh', 'PNS', 'Pelajar/Mahasiswa']),
            'status_perkawinan' => fake()->randomElement(Penduduk::STATUS_PERKAWINAN),
            'hubungan_keluarga' => 'Kepala Keluarga',
            'alamat' => fake()->streetAddress(),
            'wilayah_id' => null,
            'status' => 'hidup',
        ];
    }
}

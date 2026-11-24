<?php

namespace Database\Seeders;

use Illuminate\Support\Carbon;

/**
 * Pembangkit data keluarga (KK + anggota) realistis untuk seeder.
 * NIK dibentuk: 6 digit kode wilayah + ddmmyy lahir (+40 pada tanggal untuk perempuan) + 4 digit urut.
 */
class GeneratorKeluarga
{
    private int $kk = 0;

    /** @var array<string, true> */
    private array $nikTerpakai = [];

    private const PEKERJAAN = ['Petani', 'Nelayan', 'Pekebun', 'Wiraswasta', 'Buruh Harian Lepas', 'Pedagang', 'PNS', 'Guru', 'Karyawan Swasta'];

    public function __construct(private string $kodeWilayah)
    {
    }

    /**
     * @param  array{id:int,label:string}  $wilayah
     * @return array{kk: array, anggota: array<int, array>}
     */
    public function buat(array $wilayah, ?string $nikKepala = null): array
    {
        $this->kk++;
        $noKk = $this->kodeWilayah.str_pad((string) $this->kk, 10, '0', STR_PAD_LEFT);
        $now = now();
        $alamat = $wilayah['label'];

        $umurKepala = random_int(26, 65);
        $kepalaL = random_int(1, 100) <= 85;
        $anggota = [];

        $anggota[] = $this->orang($noKk, $wilayah, $kepalaL, $umurKepala, 'Kepala Keluarga', 'Kawin', $nikKepala);

        if (random_int(1, 100) <= 85) {
            $anggota[] = $this->orang($noKk, $wilayah, ! $kepalaL, max(18, $umurKepala - random_int(0, 8)), $kepalaL ? 'Istri' : 'Lainnya', 'Kawin');
        }

        $jumlahAnak = $umurKepala < 30 ? random_int(0, 1) : random_int(0, 3);
        for ($i = 0; $i < $jumlahAnak; $i++) {
            $umur = random_int(0, max(0, $umurKepala - 20));
            $anggota[] = $this->orang($noKk, $wilayah, (bool) random_int(0, 1), $umur, 'Anak', $umur >= 24 && random_int(0, 1) ? 'Kawin' : 'Belum Kawin');
        }

        return [
            'kk' => [
                'no_kk' => $noKk,
                'nik_kepala_keluarga' => $anggota[0]['nik'],
                'alamat' => $alamat,
                'wilayah_id' => $wilayah['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            'anggota' => $anggota,
        ];
    }

    private function orang(string $noKk, array $wilayah, bool $lakiLaki, int $umur, string $hubungan, string $perkawinan, ?string $nikPaksa = null): array
    {
        $lahir = Carbon::now()->subYears($umur)->subDays(random_int(0, 360));
        $nama = $this->nama($lakiLaki);

        $nik = $nikPaksa ?? $this->nik($lahir, $lakiLaki);
        $this->nikTerpakai[$nik] = true;

        return [
            'nik' => $nik,
            'no_kk' => $noKk,
            'nama' => $nama,
            'jenis_kelamin' => $lakiLaki ? 'L' : 'P',
            'tempat_lahir' => random_int(1, 100) <= 80 ? 'Kuala Dendang' : fake()->city(),
            'tanggal_lahir' => $lahir->toDateString(),
            'agama' => random_int(1, 100) <= 92 ? 'Islam' : fake()->randomElement(['Kristen', 'Buddha', 'Katolik']),
            'pekerjaan' => $umur < 7 ? 'Belum/Tidak Bekerja' : ($umur < 22 ? 'Pelajar/Mahasiswa' : fake()->randomElement(self::PEKERJAAN)),
            'status_perkawinan' => $perkawinan,
            'hubungan_keluarga' => $hubungan,
            'alamat' => $wilayah['label'],
            'wilayah_id' => $wilayah['id'],
            'status' => random_int(1, 100) <= 97 ? 'hidup' : fake()->randomElement(['meninggal', 'pindah']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function nama(bool $lakiLaki): string
    {
        return fake()->name($lakiLaki ? 'male' : 'female');
    }

    private function nik(Carbon $lahir, bool $lakiLaki): string
    {
        do {
            $hari = $lahir->day + ($lakiLaki ? 0 : 40);
            $nik = $this->kodeWilayah
                .str_pad((string) $hari, 2, '0', STR_PAD_LEFT)
                .$lahir->format('my')
                .str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (isset($this->nikTerpakai[$nik]));

        return $nik;
    }
}

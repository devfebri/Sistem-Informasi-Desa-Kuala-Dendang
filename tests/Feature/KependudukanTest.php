<?php

use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use App\Models\User;
use App\Models\Wilayah;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->wilayah = Wilayah::factory()->create();
    $this->kk = KartuKeluarga::factory()->create(['wilayah_id' => $this->wilayah->id]);
});

function dataPenduduk(array $override = []): array
{
    return array_merge([
        'nik' => '1505010101900099', 'no_kk' => test()->kk->no_kk, 'nama' => 'Siti Aminah',
        'jenis_kelamin' => 'P', 'tempat_lahir' => 'Kuala Dendang', 'tanggal_lahir' => '1990-01-01',
        'agama' => 'Islam', 'pekerjaan' => 'Petani', 'status_perkawinan' => 'Kawin',
        'hubungan_keluarga' => 'Istri', 'alamat' => 'Dusun A', 'wilayah_id' => test()->wilayah->id, 'status' => 'hidup',
    ], $override);
}

test('halaman kependudukan hanya untuk admin', function () {
    foreach (['admin.penduduk.index', 'admin.kartu-keluarga.index', 'admin.wilayah.index'] as $rute) {
        $this->get(route($rute))->assertOk();
    }

    $this->actingAs(User::factory()->warga()->create())
        ->get(route('admin.penduduk.index'))->assertForbidden();
});

test('tambah penduduk valid', function () {
    $this->postJson(route('admin.penduduk.store'), dataPenduduk())->assertOk();

    expect(Penduduk::where('nik', '1505010101900099')->exists())->toBeTrue();
});

test('validasi NIK 16 digit angka, unik, dan KK harus ada', function () {
    Penduduk::factory()->create(['nik' => '1505010101900001', 'no_kk' => $this->kk->no_kk]);

    $this->postJson(route('admin.penduduk.store'), dataPenduduk(['nik' => '123abc']))
        ->assertJsonValidationErrors('nik');
    $this->postJson(route('admin.penduduk.store'), dataPenduduk(['nik' => '1505010101900001']))
        ->assertJsonValidationErrors('nik');
    $this->postJson(route('admin.penduduk.store'), dataPenduduk(['no_kk' => '9999999999999999']))
        ->assertJsonValidationErrors('no_kk');
});

test('NIK tidak berubah saat edit', function () {
    $p = Penduduk::factory()->create(['nik' => '1505010101900001', 'no_kk' => $this->kk->no_kk]);

    $this->putJson(route('admin.penduduk.update', $p), dataPenduduk(['nik' => '1505010101900777', 'nama' => 'Baru']))
        ->assertOk();

    expect($p->refresh())->nik->toBe('1505010101900001')->nama->toBe('Baru');
});

test('tabel penduduk server-side: pagination, cari NIK/nama, filter', function () {
    Penduduk::factory()->count(25)->create(['no_kk' => $this->kk->no_kk, 'wilayah_id' => $this->wilayah->id]);
    Penduduk::factory()->create(['nik' => '1505019999999999', 'nama' => 'Zulkarnain', 'no_kk' => $this->kk->no_kk, 'status' => 'pindah']);

    $url = route('admin.penduduk.data');

    $this->getJson($url.'?draw=1&start=0&length=10')
        ->assertOk()->assertJsonPath('recordsTotal', 26)->assertJsonCount(10, 'data');

    $this->getJson($url.'?draw=1&start=0&length=10&search[value]=15050199')
        ->assertJsonPath('recordsFiltered', 1);

    $this->getJson($url.'?draw=1&start=0&length=10&search[value]=zulkar')
        ->assertJsonPath('recordsFiltered', 1);

    $this->getJson($url.'?draw=1&start=0&length=10&status=pindah')
        ->assertJsonPath('recordsFiltered', 1);

    // geojson / kolom berat tidak ikut terkirim
    $this->getJson($url.'?draw=1&start=0&length=1')->assertJsonMissingPath('data.0.geojson');
});

test('CRUD kartu keluarga dan validasi No. KK', function () {
    $payload = ['no_kk' => '1505010000009999', 'alamat' => 'Jl. Desa', 'wilayah_id' => $this->wilayah->id];

    $this->postJson(route('admin.kartu-keluarga.store'), $payload)->assertOk();
    $this->postJson(route('admin.kartu-keluarga.store'), $payload)->assertJsonValidationErrors('no_kk');
    $this->postJson(route('admin.kartu-keluarga.store'), ['no_kk' => '12'] + $payload)->assertJsonValidationErrors('no_kk');

    $kk = KartuKeluarga::where('no_kk', '1505010000009999')->first();
    $this->getJson(route('admin.kartu-keluarga.data', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk();
    $this->deleteJson(route('admin.kartu-keluarga.destroy', $kk))->assertOk();
});

test('KK yang masih punya anggota tidak dapat dihapus', function () {
    Penduduk::factory()->create(['no_kk' => $this->kk->no_kk]);

    $this->deleteJson(route('admin.kartu-keluarga.destroy', $this->kk))->assertUnprocessable();
});

test('wilayah: RT/RW dinormalisasi, unik, dan tidak dapat dihapus bila dipakai', function () {
    $this->postJson(route('admin.wilayah.store'), ['nama_dusun' => 'Dusun Baru', 'rt' => '1', 'rw' => '2'])->assertOk();
    expect(Wilayah::where('nama_dusun', 'Dusun Baru')->first())->rt->toBe('001')->rw->toBe('002');

    $this->postJson(route('admin.wilayah.store'), ['nama_dusun' => 'Dusun Baru', 'rt' => '001', 'rw' => '002'])
        ->assertJsonValidationErrors('rw');

    $this->deleteJson(route('admin.wilayah.destroy', $this->wilayah))->assertUnprocessable();
});

test('pencarian Select2 mengembalikan format results/pagination', function () {
    Penduduk::factory()->create(['nik' => '1505010101900123', 'nama' => 'Budi Santoso', 'no_kk' => $this->kk->no_kk]);

    $this->getJson(route('admin.cari.penduduk', ['q' => 'Budi']))
        ->assertJsonPath('results.0.id', '1505010101900123')
        ->assertJsonPath('pagination.more', false);

    $this->getJson(route('admin.cari.kk', ['q' => substr($this->kk->no_kk, 0, 6)]))
        ->assertJsonPath('results.0.id', $this->kk->no_kk);
});

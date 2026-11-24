<?php

use App\Models\BidangTanah;
use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use App\Models\User;
use App\Models\Wilayah;
use App\Support\Geo;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->wilayah = Wilayah::factory()->create();
    $this->pemilik = Penduduk::factory()->create(['nik' => '1505010101900001']);
});

function persegiJson(float $lng = 103.0, float $lat = -1.0, float $sisi = 100): string
{
    return json_encode(BidangTanah::factory()->persegi($lng, $lat, $sisi)->make()->geojson);
}

function dataBidang(array $o = []): array
{
    return array_merge([
        'no_persil' => 'PRS-001', 'nik_pemilik' => '1505010101900001', 'luas_m2' => 10000,
        'status_hak' => 'SKT', 'penggunaan_lahan' => 'pemukiman', 'wilayah_id' => test()->wilayah->id,
        'geojson' => persegiJson(),
    ], $o);
}

test('luas geodesik sesuai Turf (persegi 100 m ≈ 10.000 m²)', function () {
    $geom = json_decode(persegiJson(), true);

    expect(Geo::area($geom))->toBeBetween(9950, 10050);
});

test('normalisasi GeoJSON: Feature, MultiPolygon, tutup ring, tolak tidak valid', function () {
    $geom = json_decode(persegiJson(), true);

    expect(Geo::normalizePolygon(['type' => 'Feature', 'geometry' => $geom, 'properties' => []]))->toEqual($geom);
    expect(Geo::normalizePolygon(['type' => 'MultiPolygon', 'coordinates' => [$geom['coordinates']]]))->toEqual($geom);

    $terbuka = $geom;
    array_pop($terbuka['coordinates'][0]);
    expect(Geo::normalizePolygon($terbuka)['coordinates'][0])->toHaveCount(5);

    expect(Geo::normalizePolygon('bukan json'))->toBeNull()
        ->and(Geo::normalizePolygon(['type' => 'Point', 'coordinates' => [1, 2]]))->toBeNull()
        ->and(Geo::normalizePolygon(['type' => 'Polygon', 'coordinates' => [[[999, 0], [1, 1], [2, 2], [999, 0]]]]))->toBeNull();
});

test('halaman bidang tanah dapat dibuka admin', function () {
    $b = BidangTanah::factory()->persegi()->create(['nik_pemilik' => $this->pemilik->nik, 'wilayah_id' => $this->wilayah->id]);

    foreach ([route('admin.bidang-tanah.index'), route('admin.bidang-tanah.create'), route('admin.bidang-tanah.edit', $b),
        route('admin.bidang-tanah.show', $b), route('admin.peta')] as $url) {
        $this->get($url)->assertOk();
    }
});

test('simpan bidang tanah: luas geometri & bbox dihitung server', function () {
    $this->postJson(route('admin.bidang-tanah.store'), dataBidang())->assertOk()->assertJsonStructure(['message', 'redirect']);

    $b = BidangTanah::firstWhere('no_persil', 'PRS-001');
    expect($b->luas_geometri_m2)->toBeBetween(9950, 10050)
        ->and((float) $b->min_lng)->toBe(103.0)
        ->and($b->geojson['type'])->toBe('Polygon')
        ->and($b->pemilik->nama)->toBe($this->pemilik->nama)
        ->and($this->pemilik->bidangTanah)->toHaveCount(1);
});

test('validasi bidang tanah', function () {
    BidangTanah::factory()->persegi()->create(['no_persil' => 'PRS-001', 'nik_pemilik' => $this->pemilik->nik, 'wilayah_id' => $this->wilayah->id]);
    $url = route('admin.bidang-tanah.store');

    $this->postJson($url, dataBidang())->assertJsonValidationErrors('no_persil');
    $this->postJson($url, dataBidang(['no_persil' => 'X', 'nik_pemilik' => '9999999999999999']))->assertJsonValidationErrors('nik_pemilik');
    $this->postJson($url, dataBidang(['no_persil' => 'X', 'geojson' => '']))->assertJsonValidationErrors('geojson');
    $this->postJson($url, dataBidang(['no_persil' => 'X', 'geojson' => '{"type":"Point","coordinates":[1,1]}']))->assertJsonValidationErrors('geojson');
    $this->postJson($url, dataBidang(['no_persil' => 'X', 'status_hak' => 'SHM']))->assertJsonValidationErrors('no_sertifikat');
    $this->postJson($url, dataBidang(['no_persil' => 'X', 'status_hak' => 'SHM', 'no_sertifikat' => 'SHM-1']))->assertOk();
});

test('ubah dan hapus bidang tanah', function () {
    $b = BidangTanah::factory()->persegi()->create(['nik_pemilik' => $this->pemilik->nik, 'wilayah_id' => $this->wilayah->id]);

    $this->putJson(route('admin.bidang-tanah.update', $b), dataBidang(['no_persil' => $b->no_persil, 'penggunaan_lahan' => 'sawah']))->assertOk();
    expect($b->refresh()->penggunaan_lahan)->toBe('sawah');

    $this->deleteJson(route('admin.bidang-tanah.destroy', $b))->assertOk();
    $this->assertModelMissing($b);
});

test('penduduk pemilik bidang tanah tidak dapat dihapus', function () {
    BidangTanah::factory()->persegi()->create(['nik_pemilik' => $this->pemilik->nik, 'wilayah_id' => $this->wilayah->id]);

    $this->deleteJson(route('admin.penduduk.destroy', $this->pemilik))->assertUnprocessable();
});

test('tabel bidang tanah server-side tanpa geojson', function () {
    BidangTanah::factory()->count(12)->persegi()->create(['nik_pemilik' => $this->pemilik->nik, 'wilayah_id' => $this->wilayah->id]);

    $this->getJson(route('admin.bidang-tanah.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->assertOk()->assertJsonPath('recordsTotal', 12)->assertJsonCount(10, 'data')
        ->assertJsonMissingPath('data.0.geojson');
});

test('API geojson: hanya fitur dalam bbox, filter, dan exclude', function () {
    $dalam = BidangTanah::factory()->persegi(103.000, -1.000)->create(['nik_pemilik' => $this->pemilik->nik, 'wilayah_id' => $this->wilayah->id, 'status_hak' => 'SHM']);
    BidangTanah::factory()->persegi(103.500, -1.500)->create(['nik_pemilik' => $this->pemilik->nik, 'wilayah_id' => $this->wilayah->id]);

    $url = route('api.bidang-tanah.geojson');

    $this->getJson($url)->assertJsonCount(2, 'features');

    $this->getJson($url.'?bbox=102.99,-1.01,103.01,-0.99')
        ->assertOk()->assertJsonPath('type', 'FeatureCollection')
        ->assertJsonCount(1, 'features')
        ->assertJsonPath('features.0.properties.id', $dalam->id)
        ->assertJsonPath('features.0.geometry.type', 'Polygon');

    $this->getJson($url.'?status_hak=SHM')->assertJsonCount(1, 'features');
    $this->getJson($url.'?exclude='.$dalam->id)->assertJsonCount(1, 'features');
});

test('popup, pencarian peta, dan batas wilayah; kades boleh baca, warga tidak', function () {
    $b = BidangTanah::factory()->persegi()->create(['no_persil' => 'PRS-777', 'nik_pemilik' => $this->pemilik->nik, 'wilayah_id' => $this->wilayah->id]);
    $this->wilayah->update(['geojson' => json_decode(persegiJson(), true)]);

    $this->actingAs(User::factory()->kades()->create());

    $this->get(route('api.bidang-tanah.popup', $b))->assertOk()->assertSee('PRS-777')->assertSee($this->pemilik->nama);
    $this->getJson(route('api.bidang-tanah.cari', ['q' => 'PRS-7']))->assertJsonPath('results.0.id', $b->id)->assertJsonCount(4, 'results.0.bbox');
    $this->getJson(route('api.bidang-tanah.cari', ['q' => '1505010101']))->assertJsonCount(1, 'results');
    $this->getJson(route('api.wilayah.geojson'))->assertJsonCount(1, 'features');
    $this->get(route('kades.peta'))->assertOk();

    $this->actingAs(User::factory()->warga()->create());
    $this->getJson(route('api.bidang-tanah.geojson'))->assertForbidden();
});

test('batas wilayah GeoJSON dapat disimpan dan divalidasi', function () {
    $this->putJson(route('admin.wilayah.update', $this->wilayah), [
        'nama_dusun' => $this->wilayah->nama_dusun, 'rt' => $this->wilayah->rt, 'rw' => $this->wilayah->rw, 'geojson' => persegiJson(),
    ])->assertOk();
    expect($this->wilayah->refresh()->geojson['type'])->toBe('Polygon');

    $this->putJson(route('admin.wilayah.update', $this->wilayah), [
        'nama_dusun' => $this->wilayah->nama_dusun, 'rt' => $this->wilayah->rt, 'rw' => $this->wilayah->rw, 'geojson' => 'ngawur',
    ])->assertJsonValidationErrors('geojson');
});

<?php

use App\Models\BidangTanah;
use App\Models\Penduduk;
use App\Models\PeralihanHak;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

    $this->wilayah = Wilayah::factory()->create();
    $this->lama = Penduduk::factory()->create(['nik' => '1505010101900001', 'nama' => 'Pemilik Lama']);
    $this->baru = Penduduk::factory()->create(['nik' => '1505010101900002', 'nama' => 'Pemilik Baru']);
    $this->bidang = BidangTanah::factory()->persegi()->create([
        'no_persil' => 'PRS-100', 'nik_pemilik' => $this->lama->nik, 'wilayah_id' => $this->wilayah->id,
    ]);
});

function dataPeralihan(array $o = []): array
{
    return array_merge([
        'bidang_tanah_id' => test()->bidang->id,
        'nik_pemilik_lama' => '1505010101900001',
        'nik_pemilik_baru' => '1505010101900002',
        'jenis' => 'jual_beli',
        'tanggal' => now()->subDay()->toDateString(),
        'no_akta' => 'AKTA/001',
    ], $o);
}

test('halaman peralihan hak dapat dibuka, termasuk preselect bidang dari detail', function () {
    $this->get(route('admin.peralihan-hak.index'))->assertOk();
    $this->get(route('admin.peralihan-hak.index', ['bidang_tanah_id' => $this->bidang->id]))
        ->assertOk()->assertSee('PRS-100');
});

test('peralihan menyimpan riwayat dan memperbarui pemilik dalam satu transaksi', function () {
    $this->postJson(route('admin.peralihan-hak.store'), dataPeralihan())->assertOk();

    expect($this->bidang->refresh()->nik_pemilik)->toBe('1505010101900002');

    $r = PeralihanHak::first();
    expect($r)->bidang_tanah_id->toBe($this->bidang->id)
        ->nik_pemilik_lama->toBe('1505010101900001')
        ->nik_pemilik_baru->toBe('1505010101900002')
        ->jenis->toBe('jual_beli')
        ->created_by->toBe($this->admin->id);

    // Penduduk lama tidak lagi memiliki bidang ini, pemilik baru memilikinya.
    expect($this->lama->bidangTanah()->count())->toBe(0)->and($this->baru->bidangTanah()->count())->toBe(1);
});

test('dokumen disimpan di disk privat dan hanya bisa diakses lewat route berotorisasi', function () {
    $this->postJson(route('admin.peralihan-hak.store'), dataPeralihan([
        'dokumen' => UploadedFile::fake()->create('akta.pdf', 200, 'application/pdf'),
    ]))->assertOk();

    $r = PeralihanHak::first();
    Storage::disk('local')->assertExists($r->dokumen);

    $this->get(route('admin.peralihan-hak.dokumen', $r))->assertOk();

    $this->actingAs(User::factory()->warga()->create())
        ->get(route('admin.peralihan-hak.dokumen', $r))->assertForbidden();
});

test('validasi: pemilik baru berbeda, hidup, ada; jenis; tanggal; dokumen', function () {
    $url = route('admin.peralihan-hak.store');

    $this->postJson($url, dataPeralihan(['nik_pemilik_baru' => '1505010101900001']))->assertJsonValidationErrors('nik_pemilik_baru');
    $this->postJson($url, dataPeralihan(['nik_pemilik_baru' => '9999999999999999']))->assertJsonValidationErrors('nik_pemilik_baru');
    $this->postJson($url, dataPeralihan(['jenis' => 'curian']))->assertJsonValidationErrors('jenis');
    $this->postJson($url, dataPeralihan(['tanggal' => now()->addDay()->toDateString()]))->assertJsonValidationErrors('tanggal');
    $this->postJson($url, dataPeralihan(['dokumen' => UploadedFile::fake()->create('x.exe', 10)]))->assertJsonValidationErrors('dokumen');
    $this->postJson($url, dataPeralihan(['dokumen' => UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf')]))->assertJsonValidationErrors('dokumen');

    $this->baru->update(['status' => 'meninggal']);
    $this->postJson($url, dataPeralihan())->assertJsonValidationErrors('nik_pemilik_baru');

    expect(PeralihanHak::count())->toBe(0)->and($this->bidang->refresh()->nik_pemilik)->toBe('1505010101900001');
});

test('ditolak bila pemilik lama sudah berubah (data basi) dan tidak ada yang tersimpan', function () {
    $lain = Penduduk::factory()->create(['nik' => '1505010101900003']);
    $this->bidang->update(['nik_pemilik' => $lain->nik]);

    $this->postJson(route('admin.peralihan-hak.store'), dataPeralihan([
        'dokumen' => UploadedFile::fake()->create('akta.pdf', 10, 'application/pdf'),
    ]))->assertUnprocessable()->assertJsonPath('message', fn ($m) => str_contains($m, 'telah berubah'));

    expect(PeralihanHak::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('dua peralihan berurutan membentuk riwayat berantai', function () {
    $c = Penduduk::factory()->create(['nik' => '1505010101900004']);

    $this->postJson(route('admin.peralihan-hak.store'), dataPeralihan())->assertOk();
    $this->postJson(route('admin.peralihan-hak.store'), dataPeralihan([
        'nik_pemilik_lama' => '1505010101900002', 'nik_pemilik_baru' => $c->nik, 'jenis' => 'hibah',
    ]))->assertOk();

    expect($this->bidang->refresh()->nik_pemilik)->toBe($c->nik)
        ->and($this->bidang->peralihanHak)->toHaveCount(2);

    $this->get(route('admin.bidang-tanah.show', $this->bidang))
        ->assertOk()->assertSee('Riwayat Kepemilikan')->assertSee('Pemilik Lama')->assertSee('Hibah');
});

test('tabel peralihan hak server-side dan pencarian', function () {
    $this->postJson(route('admin.peralihan-hak.store'), dataPeralihan())->assertOk();

    $url = route('admin.peralihan-hak.data', ['draw' => 1, 'start' => 0, 'length' => 10]);
    $this->getJson($url)->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonCount(1, 'data');
    $this->getJson($url.'&search[value]=PRS-100')->assertJsonPath('recordsFiltered', 1);
    $this->getJson($url.'&search[value]=PRS-999')->assertJsonPath('recordsFiltered', 0);
    $this->getJson($url.'&jenis=waris')->assertJsonPath('recordsFiltered', 0);
});

test('riwayat tidak dapat diubah/dihapus dan melindungi data terkait', function () {
    $this->postJson(route('admin.peralihan-hak.store'), dataPeralihan())->assertOk();
    $r = PeralihanHak::first();

    $this->putJson('/admin/peralihan-hak/'.$r->id, [])->assertNotFound();
    $this->deleteJson('/admin/peralihan-hak/'.$r->id)->assertNotFound();

    $this->deleteJson(route('admin.bidang-tanah.destroy', $this->bidang))->assertUnprocessable();
    $this->deleteJson(route('admin.penduduk.destroy', $this->lama))->assertUnprocessable();
});

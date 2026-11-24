<?php

use App\Models\BidangTanah;
use App\Models\LogAktivitas;
use App\Models\Penduduk;
use App\Models\User;
use App\Models\Wilayah;
use App\Support\Samar;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->wilayah = Wilayah::factory()->create();
    $this->budi = Penduduk::factory()->create(['nik' => '1505010101900001', 'nama' => 'Budi Santoso']);
    $this->ani = Penduduk::factory()->create(['nik' => '1505010101900002', 'nama' => 'Ani Lestari']);
    $this->warga = User::factory()->warga()->create(['nik' => $this->budi->nik, 'name' => 'Budi']);

    $this->milik = BidangTanah::factory()->persegi(103.0)->create([
        'no_persil' => 'PRS-001', 'nik_pemilik' => $this->budi->nik, 'wilayah_id' => $this->wilayah->id, 'no_sertifikat' => 'SHM-RAHASIA-77', 'status_hak' => 'SHM']);
    $this->orangLain = BidangTanah::factory()->persegi(103.1)->create([
        'no_persil' => 'PRS-002', 'nik_pemilik' => $this->ani->nik, 'wilayah_id' => $this->wilayah->id]);
});

// -------------------------------------------------------------- peta warga

test('penyamaran nama', function () {
    expect(Samar::nama('Budi Santoso'))->toBe('B*** S******')->and(Samar::nama(null))->toBe('');
});

test('API peta warga tidak membocorkan NIK, sertifikat, atau nama asli', function () {
    $this->actingAs($this->warga);

    $res = $this->getJson(route('warga.api.geojson'))->assertOk()->assertJsonCount(2, 'features');
    $json = $res->getContent();

    expect($json)->not->toContain('1505010101900001')->not->toContain('1505010101900002')
        ->not->toContain('nik_pemilik')->not->toContain('SHM-RAHASIA-77')->not->toContain('Santoso');

    $fitur = collect($res->json('features'))->keyBy('properties.no_persil');
    expect($fitur['PRS-001']['properties']['milik_saya'])->toBeTrue()
        ->and($fitur['PRS-002']['properties']['milik_saya'])->toBeFalse();
});

test('popup peta warga menyamarkan pemilik dan menandai milik sendiri', function () {
    $this->actingAs($this->warga);

    $this->get(route('warga.api.popup', $this->orangLain))->assertOk()
        ->assertSee('A**')->assertDontSee('Ani Lestari')->assertDontSee('1505010101900002')
        ->assertDontSee('Milik Anda');

    $this->get(route('warga.api.popup', $this->milik))->assertOk()
        ->assertSee('Milik Anda')->assertDontSee('SHM-RAHASIA-77')->assertDontSee('1505010101900001');
});

test('pencarian peta warga hanya dengan no. persil, bukan NIK', function () {
    $this->actingAs($this->warga);

    $this->getJson(route('warga.api.cari', ['q' => 'PRS-00']))->assertJsonCount(2, 'results');
    $this->getJson(route('warga.api.cari', ['q' => '1505010101900002']))->assertJsonCount(0, 'results');
    $this->getJson(route('warga.api.cari', ['q' => 'PRS']))->assertJsonMissingPath('results.0.pemilik_nik');
});

test('halaman peta warga dapat dibuka; API petugas tertutup untuk warga', function () {
    $this->actingAs($this->warga)->get(route('warga.peta'))->assertOk()->assertSee('Cari No. persil');
    $this->get(route('warga.api.geojson').'?bbox=102.9,-1.1,103.05,-0.9')->assertJsonCount(1, 'features');
    $this->getJson(route('api.bidang-tanah.geojson'))->assertForbidden();
    $this->getJson(route('api.bidang-tanah.cari'))->assertForbidden();
    $this->getJson(route('api.wilayah.publik'))->assertOk();

    $this->actingAs(User::factory()->admin()->create())->get(route('warga.peta'))->assertForbidden();
});

test('peta petugas tetap menampilkan pencarian NIK', function () {
    $this->actingAs(User::factory()->kades()->create())->get(route('kades.peta'))->assertOk()->assertSee('NIK pemilik');
});

// ---------------------------------------------------------- log aktivitas

test('perubahan data oleh pengguna login dicatat ke log', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    LogAktivitas::query()->delete();

    $this->postJson(route('admin.wilayah.store'), ['nama_dusun' => 'Dusun Log', 'rt' => '1', 'rw' => '1'])->assertOk();
    $w = Wilayah::firstWhere('nama_dusun', 'Dusun Log');
    $this->putJson(route('admin.wilayah.update', $w), ['nama_dusun' => 'Dusun Log Baru', 'rt' => '1', 'rw' => '1'])->assertOk();
    $this->deleteJson(route('admin.wilayah.destroy', $w))->assertOk();

    $log = LogAktivitas::query()->where('model', 'Wilayah')->orderBy('id')->get();

    expect($log->pluck('aksi')->all())->toBe(['tambah', 'ubah', 'hapus'])
        ->and($log[0])->user_id->toBe($admin->id)->model_id->toBe($w->id)
        ->and($log[1]->perubahan)->toMatchArray(['nama_dusun' => 'Dusun Log Baru'])
        ->and($log[2]->keterangan)->toBe('Dusun Log Baru');
});

test('log tidak menyimpan kata sandi atau geometri mentah', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $user = User::factory()->warga()->create();
    LogAktivitas::query()->delete();

    $user->update(['password' => 'rahasia-baru']);
    $this->milik->update(['geojson' => $this->orangLain->geojson]);

    $semua = LogAktivitas::all();
    expect($semua->toJson())->not->toContain('rahasia-baru')->not->toContain('coordinates');
    expect($semua->firstWhere('model', 'User')->perubahan['password'])->toBe('[diganti]')
        ->and($semua->firstWhere('model', 'BidangTanah')->perubahan['geojson'])->toBe('[geometri diubah]');
});

test('tanpa pengguna login (seeder/konsol) tidak dicatat', function () {
    LogAktivitas::query()->delete();

    Wilayah::factory()->create();

    expect(LogAktivitas::count())->toBe(0);
});

test('login, logout, dan login gagal dicatat', function () {
    $u = User::factory()->admin()->create(['username' => 'adminlog']);
    LogAktivitas::query()->delete();

    $this->post('/login', ['username' => 'adminlog', 'password' => 'salah']);
    $this->post('/login', ['username' => 'adminlog', 'password' => 'password']);
    $this->post('/logout');

    expect(LogAktivitas::orderBy('id')->pluck('aksi')->all())->toBe(['login_gagal', 'login', 'logout']);
    expect(LogAktivitas::firstWhere('aksi', 'login_gagal')->keterangan)->toBe('adminlog');
    expect(LogAktivitas::all()->toJson())->not->toContain('salah');
});

test('halaman log aktivitas hanya untuk admin', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.log.index'))->assertOk();

    Wilayah::factory()->create(); // tercatat (admin sedang login)
    $this->getJson(route('admin.log.data', ['draw' => 1, 'start' => 0, 'length' => 10, 'model' => 'Wilayah']))
        ->assertOk()->assertJsonPath('recordsFiltered', 1);

    $this->actingAs(User::factory()->kades()->create())->get(route('admin.log.index'))->assertForbidden();
    $this->actingAs($this->warga)->getJson(route('admin.log.data'))->assertForbidden();
});

// ------------------------------------------------------------------ profil

test('semua role dapat membuka profil; warga melihat data kependudukannya', function () {
    foreach ([User::factory()->admin()->create(), User::factory()->kades()->create()] as $u) {
        $this->actingAs($u)->get(route('profil.show'))->assertOk()->assertSee('Profil Saya', false);
    }

    $this->actingAs($this->warga)->get(route('profil.show'))->assertOk()->assertSee('Data Kependudukan')->assertSee('1505010101900001');
});

test('ubah nama tanpa dapat mengubah username atau role', function () {
    $this->actingAs($this->warga)->putJson(route('profil.update'), ['name' => 'Budi Baru', 'role' => 'admin', 'username' => 'hacker'])->assertOk();

    expect($this->warga->refresh())->name->toBe('Budi Baru')->role->toBe('warga')->username->not->toBe('hacker');
    $this->putJson(route('profil.update'), ['name' => ''])->assertJsonValidationErrors('name');
});

test('ganti kata sandi memerlukan kata sandi lama yang benar', function () {
    $this->actingAs($this->warga);
    $url = route('profil.password');

    $this->putJson($url, ['password_lama' => 'salah', 'password' => 'baru123', 'password_confirmation' => 'baru123'])->assertJsonValidationErrors('password_lama');
    $this->putJson($url, ['password_lama' => 'password', 'password' => 'baru123', 'password_confirmation' => 'beda'])->assertJsonValidationErrors('password');
    $this->putJson($url, ['password_lama' => 'password', 'password' => 'password', 'password_confirmation' => 'password'])->assertJsonValidationErrors('password');

    $this->putJson($url, ['password_lama' => 'password', 'password' => 'baru123', 'password_confirmation' => 'baru123'])->assertOk();
    expect(Hash::check('baru123', $this->warga->refresh()->password))->toBeTrue();
});

test('beranda warga menampilkan ringkasan', function () {
    $this->actingAs($this->warga)->get(route('warga.beranda'))->assertOk()
        ->assertSee('Selamat datang, Budi Santoso')->assertSee('Bidang tanah saya');
});

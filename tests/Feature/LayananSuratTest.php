<?php

use App\Models\BidangTanah;
use App\Models\DokumenPengajuan;
use App\Models\JenisSurat;
use App\Models\Penduduk;
use App\Models\PengajuanSurat;
use App\Models\User;
use App\Models\Wilayah;
use App\Support\SketsaPersil;
use Database\Seeders\JenisSuratSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(JenisSuratSeeder::class);

    $this->admin = User::factory()->admin()->create(['name' => 'Admin Desa']);
    $this->kades = User::factory()->kades()->create(['name' => 'Bapak Kades']);

    $this->penduduk = Penduduk::factory()->create(['nik' => '1505010101900001', 'nama' => 'Budi Santoso']);
    $this->warga = User::factory()->warga()->create(['nik' => $this->penduduk->nik]);
    $this->bidang = BidangTanah::factory()->persegi()->create([
        'no_persil' => 'PRS-001', 'nik_pemilik' => $this->penduduk->nik, 'wilayah_id' => Wilayah::factory()->create()->id,
        'no_sertifikat' => null,
    ]);
    $this->skt = JenisSurat::firstWhere('kode', 'SKT');
});

function berkas(JenisSurat $j): array
{
    return collect($j->persyaratan)->mapWithKeys(fn ($n, $i) => [$i => UploadedFile::fake()->create("b$i.pdf", 100, 'application/pdf')])->all();
}

function ajukan(array $o = [])
{
    $t = test();

    return $t->actingAs($t->warga)->postJson(route('warga.surat.store'), array_merge([
        'jenis_surat_id' => $t->skt->id, 'bidang_tanah_id' => $t->bidang->id,
        'keperluan' => 'Pengurusan sertifikat', 'dokumen' => berkas($t->skt),
    ], $o));
}

function pengajuan(string $status = 'diajukan', array $o = []): PengajuanSurat
{
    $p = PengajuanSurat::factory()->create(array_merge([
        'bidang_tanah_id' => test()->bidang->id, 'jenis_surat_id' => test()->skt->id, 'status' => $status,
    ], $o));
    $p->update(['no_registrasi' => 'REG-TEST-'.$p->id]);

    return $p;
}

function terbitkan(PengajuanSurat $p): PengajuanSurat
{
    $p->update(['status' => 'diverifikasi', 'verified_by' => test()->admin->id, 'verified_at' => now()]);
    test()->actingAs(test()->kades)->postJson(route('kades.pengajuan.setujui', $p))->assertOk();

    return $p->refresh();
}

// ------------------------------------------------------------------ warga

test('warga mengajukan surat dengan berkas persyaratan', function () {
    ajukan()->assertOk();

    $p = PengajuanSurat::first();
    expect($p)->status->toBe('diajukan')->nik_pemohon->toBe('1505010101900001')
        ->and($p->no_registrasi)->toMatch('/^REG-\d{8}-\d{5}$/')
        ->and($p->dokumen)->toHaveCount(count($this->skt->persyaratan));

    foreach ($p->dokumen as $d) {
        Storage::disk('local')->assertExists($d->path_file);
    }
});

test('validasi pengajuan: berkas wajib, tipe, milik sendiri, tidak ganda', function () {
    ajukan(['dokumen' => []])->assertJsonValidationErrors(['dokumen.0', 'dokumen.1']);
    ajukan(['dokumen' => [0 => UploadedFile::fake()->create('x.exe', 10)] + berkas($this->skt)])->assertJsonValidationErrors('dokumen.0');
    ajukan(['keperluan' => ''])->assertJsonValidationErrors('keperluan');

    $orangLain = BidangTanah::factory()->persegi(103.1)->create();
    ajukan(['bidang_tanah_id' => $orangLain->id])->assertJsonValidationErrors('bidang_tanah_id');

    ajukan()->assertOk();
    ajukan()->assertJsonValidationErrors('jenis_surat_id'); // pengajuan sama masih diproses

    expect(PengajuanSurat::count())->toBe(1);
});

test('warga tanpa data penduduk tidak dapat mengajukan', function () {
    $this->warga->update(['nik' => null]);

    ajukan()->assertJsonValidationErrors('bidang_tanah_id');
});

test('warga hanya melihat pengajuan miliknya', function () {
    $milik = pengajuan();
    $lain = PengajuanSurat::factory()->create(['jenis_surat_id' => $this->skt->id]);

    $this->actingAs($this->warga)->getJson(route('warga.surat.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->assertJsonPath('recordsTotal', 1);

    $this->actingAs($this->warga)->get(route('warga.surat.show', $milik))->assertOk()->assertSee($milik->no_registrasi);
    $this->actingAs($this->warga)->get(route('warga.surat.show', $lain))->assertForbidden();
    $this->actingAs($this->warga)->get(route('warga.surat.index'))->assertOk();
    $this->actingAs($this->warga)->get(route('warga.bidang'))->assertOk();
    $this->actingAs($this->warga)->getJson(route('warga.bidang.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->assertJsonPath('recordsTotal', 1);
});

// ---------------------------------------------------------- alur petugas

test('admin memverifikasi lalu kades menyetujui dengan nomor surat & kode validasi', function () {
    $p = pengajuan();

    $this->actingAs($this->admin)->get(route('admin.pengajuan.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.pengajuan.show', $p))->assertOk();
    $this->actingAs($this->admin)->postJson(route('admin.pengajuan.verifikasi', $p))->assertOk();
    expect($p->refresh())->status->toBe('diverifikasi')->verified_by->toBe($this->admin->id);

    // Verifikasi dua kali ditolak
    $this->postJson(route('admin.pengajuan.verifikasi', $p))->assertUnprocessable();

    $this->actingAs($this->kades)->postJson(route('kades.pengajuan.setujui', $p))->assertOk();
    $p->refresh();

    expect($p)->status->toBe('disetujui')->approved_by->toBe($this->kades->id)
        ->and($p->nomor_surat)->toMatch('#^001/SKT/KD/[IVX]+/\d{4}$#')
        ->and($p->kode_validasi)->toHaveLength(12)
        ->and($p->suratTerbit())->toBeTrue();
});

test('nomor surat berurutan per tahun dan unik', function () {
    $a = terbitkan(pengajuan());
    $b = terbitkan(pengajuan('diajukan', ['jenis_surat_id' => JenisSurat::firstWhere('kode', 'SKTS')->id]));

    expect($a->no_urut)->toBe(1)->and($b->no_urut)->toBe(2)
        ->and($b->nomor_surat)->toStartWith('002/SKTS/KD/')
        ->and($a->kode_validasi)->not->toBe($b->kode_validasi);
});

test('kades tidak dapat menyetujui pengajuan yang belum diverifikasi', function () {
    $p = pengajuan('diajukan');

    $this->actingAs($this->kades)->postJson(route('kades.pengajuan.setujui', $p))->assertUnprocessable();
    expect($p->refresh()->status)->toBe('diajukan')->and($p->nomor_surat)->toBeNull();
});

test('penolakan wajib alasan; admin pada tahap diajukan, kades pada tahap diverifikasi', function () {
    $p = pengajuan('diajukan');

    $this->actingAs($this->admin)->postJson(route('admin.pengajuan.tolak', $p), ['catatan' => ''])->assertJsonValidationErrors('catatan');
    $this->postJson(route('admin.pengajuan.tolak', $p), ['catatan' => 'Berkas KTP tidak terbaca'])->assertOk();
    expect($p->refresh())->status->toBe('ditolak')->catatan->toBe('Berkas KTP tidak terbaca')->rejected_by->toBe($this->admin->id);

    $q = pengajuan('diverifikasi');
    $this->actingAs($this->kades)->postJson(route('kades.pengajuan.tolak', $q), ['catatan' => 'Data tidak sesuai'])->assertOk();
    expect($q->refresh())->status->toBe('ditolak')->rejected_by->toBe($this->kades->id);

    // Warga melihat alasan penolakan
    $this->actingAs($this->warga)->get(route('warga.surat.show', $p))->assertSee('Berkas KTP tidak terbaca');

    // Pengajuan yang sudah ditolak tidak bisa diproses lagi
    $this->actingAs($this->admin)->postJson(route('admin.pengajuan.verifikasi', $p))->assertUnprocessable();
});

test('pembatasan role pada aksi pengajuan', function () {
    $p = pengajuan('diverifikasi');

    $this->actingAs($this->admin)->postJson(route('kades.pengajuan.setujui', $p))->assertForbidden();
    $this->actingAs($this->kades)->postJson(route('admin.pengajuan.verifikasi', $p))->assertForbidden();
    $this->actingAs($this->warga)->postJson(route('kades.pengajuan.setujui', $p))->assertForbidden();
    $this->actingAs($this->warga)->get(route('admin.pengajuan.index'))->assertForbidden();
});

test('admin menandai surat selesai hanya dari status disetujui', function () {
    $p = pengajuan();
    $this->actingAs($this->admin)->postJson(route('admin.pengajuan.selesai', $p))->assertUnprocessable();

    terbitkan($p);
    $this->actingAs($this->admin)->postJson(route('admin.pengajuan.selesai', $p))->assertOk();
    expect($p->refresh())->status->toBe('selesai')->and($p->completed_at)->not->toBeNull();
});

test('antrean kades tidak memuat pengajuan yang belum diverifikasi', function () {
    pengajuan('diajukan');
    pengajuan('diverifikasi', ['jenis_surat_id' => JenisSurat::firstWhere('kode', 'SKTS')->id]);

    $this->actingAs($this->kades)->get(route('kades.pengajuan.index'))->assertOk();
    $this->getJson(route('kades.pengajuan.data', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertJsonPath('recordsTotal', 1);

    $this->actingAs($this->admin)->getJson(route('admin.pengajuan.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->assertJsonPath('recordsTotal', 2);
});

// -------------------------------------------------------------------- PDF

test('PDF surat: hanya setelah terbit, dan hanya untuk pihak berwenang', function () {
    $p = pengajuan();

    $this->actingAs($this->warga)->get(route('surat.pdf', $p))->assertNotFound();

    terbitkan($p);

    $res = $this->actingAs($this->warga)->get(route('surat.pdf', $p))->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($res->getContent(), 0, 4))->toBe('%PDF');

    $this->actingAs($this->admin)->get(route('surat.pdf', $p))->assertOk();
    $this->actingAs($this->kades)->get(route('surat.pdf', $p))->assertOk();

    $lain = User::factory()->warga()->create(['nik' => Penduduk::factory()->create()->nik]);
    $this->actingAs($lain)->get(route('surat.pdf', $p))->assertForbidden();
});

test('keempat template surat dapat dirender menjadi PDF', function () {
    foreach (JenisSurat::all() as $jenis) {
        $p = pengajuan('diajukan', ['jenis_surat_id' => $jenis->id]);
        terbitkan($p);

        $res = $this->actingAs($this->admin)->get(route('surat.pdf', $p))->assertOk();
        expect(substr($res->getContent(), 0, 4))->toBe('%PDF', "template {$jenis->kode}");

        $p->update(['status' => 'selesai']); // agar tidak dianggap pengajuan aktif ganda
    }
});

test('sketsa persil menghasilkan SVG dengan polygon, arah utara, dan skala', function () {
    $svg = SketsaPersil::svg($this->bidang->geojson, [], 'Persil X');

    expect($svg)->toStartWith('<svg')->toContain('<polygon')->toContain('>U<')->toContain('Persil X')->toContain(' m</text>');
});

// ------------------------------------------------------------- validasi QR

test('halaman validasi publik: surat valid menyamarkan data pribadi', function () {
    $p = terbitkan(pengajuan());
    auth()->logout();

    $this->get(route('validasi', $p->kode_validasi))
        ->assertOk()->assertSee('SURAT VALID')->assertSee($p->nomor_surat)
        ->assertSee('B*** S******')->assertDontSee('Budi Santoso')->assertDontSee($this->penduduk->nik);

    // huruf kecil pada URL tetap dikenali
    $this->get(route('validasi', strtolower($p->kode_validasi)))->assertSee('SURAT VALID');
});

test('validasi: kode salah atau surat belum terbit tidak valid', function () {
    $p = pengajuan();
    $p->update(['kode_validasi' => 'ABCDEFGHIJKL', 'status' => 'ditolak']);

    $this->get(route('validasi', 'ABCDEFGHIJKL'))->assertSee('SURAT TIDAK DITEMUKAN');
    $this->get(route('validasi', 'TIDAKADA0000'))->assertSee('SURAT TIDAK DITEMUKAN');
});

// ------------------------------------------------------------ berkas privat

test('berkas persyaratan hanya dapat dibuka pemilik dan petugas', function () {
    ajukan()->assertOk();
    $d = DokumenPengajuan::first();

    $this->actingAs($this->warga)->get(route('dokumen-pengajuan.unduh', $d))->assertOk();
    $this->actingAs($this->admin)->get(route('dokumen-pengajuan.unduh', $d))->assertOk();
    $this->actingAs($this->kades)->get(route('dokumen-pengajuan.unduh', $d))->assertOk();

    $lain = User::factory()->warga()->create(['nik' => Penduduk::factory()->create()->nik]);
    $this->actingAs($lain)->get(route('dokumen-pengajuan.unduh', $d))->assertForbidden();

    auth()->logout();
    $this->get(route('dokumen-pengajuan.unduh', $d))->assertRedirect(route('login'));
});

test('data yang dipakai pengajuan tidak dapat dihapus', function () {
    pengajuan();

    $this->actingAs($this->admin)->deleteJson(route('admin.bidang-tanah.destroy', $this->bidang))->assertUnprocessable();
    $this->deleteJson(route('admin.penduduk.destroy', $this->penduduk))->assertUnprocessable();
});

test('cache jenis surat hanya berisi array (aman untuk cache database/file/redis)', function () {
    Cache::forget('jenis_surat.aktif');

    $jenis = JenisSurat::aktif();
    $mentah = Cache::get('jenis_surat.aktif');

    expect($mentah)->toBeArray()->and($mentah[0])->toBeArray()
        ->and($jenis->first()->nama)->toBe($mentah[0]['nama'])
        ->and($jenis->first()->persyaratan)->toBeArray();

    // Serialisasi bolak-balik (seperti cache database) tetap menghasilkan data yang sama.
    expect(unserialize(serialize($mentah), ['allowed_classes' => false]))->toBe($mentah);
});

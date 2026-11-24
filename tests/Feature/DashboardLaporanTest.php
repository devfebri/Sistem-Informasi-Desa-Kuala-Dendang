<?php

use App\Models\BidangTanah;
use App\Models\Penduduk;
use App\Models\PeralihanHak;
use App\Models\PengajuanSurat;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\StatistikDesa;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->kades = User::factory()->kades()->create();

    $this->wilayah = Wilayah::factory()->create(['nama_dusun' => 'Dusun Uji']);
    $this->budi = Penduduk::factory()->create(['nik' => '1505010101900001', 'nama' => 'Budi Santoso', 'jenis_kelamin' => 'L', 'status' => 'hidup']);
    $this->siti = Penduduk::factory()->create(['nik' => '1505010101900002', 'nama' => 'Ani Lestari', 'jenis_kelamin' => 'P', 'status' => 'hidup']);
    Penduduk::factory()->create(['status' => 'meninggal']);

    $this->b1 = BidangTanah::factory()->persegi(103.0)->create(['no_persil' => 'PRS-1', 'nik_pemilik' => $this->budi->nik, 'wilayah_id' => $this->wilayah->id, 'luas_m2' => 1000, 'status_hak' => 'SHM', 'penggunaan_lahan' => 'sawah']);
    $this->b2 = BidangTanah::factory()->persegi(103.1)->create(['no_persil' => 'PRS-2', 'nik_pemilik' => $this->budi->nik, 'wilayah_id' => $this->wilayah->id, 'luas_m2' => 2000, 'status_hak' => 'SKT', 'penggunaan_lahan' => 'kebun']);
    $this->b3 = BidangTanah::factory()->persegi(103.2)->create(['no_persil' => 'PRS-3', 'nik_pemilik' => $this->siti->nik, 'wilayah_id' => $this->wilayah->id, 'luas_m2' => 500, 'status_hak' => 'SKT', 'penggunaan_lahan' => 'sawah']);

    Cache::forget('statistik.dashboard');
});

// -------------------------------------------------------------- dashboard

test('dashboard admin & kades menampilkan statistik', function () {
    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
        ->assertSee('Bidang Tanah')->assertSee('grafikStatusHak', false)->assertSee('menunggu verifikasi');

    $this->actingAs($this->kades)->get(route('kades.dashboard'))->assertOk()->assertSee('menunggu persetujuan');

    $this->actingAs(User::factory()->warga()->create())->get(route('admin.dashboard'))->assertForbidden();
});

test('angka statistik akurat', function () {
    PengajuanSurat::factory()->create(['bidang_tanah_id' => $this->b1->id, 'status' => 'disetujui']);
    PengajuanSurat::factory()->create(['bidang_tanah_id' => $this->b2->id, 'status' => 'diajukan']);

    $s = app(StatistikDesa::class)->ambil();

    expect($s['penduduk'])->toMatchArray(['total' => 2, 'laki' => 1, 'perempuan' => 1])  // yang meninggal tidak dihitung
        ->and($s['persil'])->toMatchArray(['total' => 3, 'luas_m2' => 3500.0])
        ->and(collect($s['status_hak'])->firstWhere('label', 'SKT'))->toMatchArray(['persil' => 2, 'luas_m2' => 2500.0])
        ->and(collect($s['status_hak'])->firstWhere('label', 'HGB')['persil'])->toBe(0)
        ->and($s['penggunaan_per_dusun']['dusun'])->toContain('Dusun Uji')
        ->and(collect($s['penggunaan_per_dusun']['seri'])->firstWhere('label', 'Sawah')['nilai'][0])->toBe(1500.0)
        ->and($s['surat_per_bulan']['nilai'])->toHaveCount(12)->and(array_sum($s['surat_per_bulan']['nilai']))->toBe(2)
        ->and(collect($s['surat_per_status'])->firstWhere('label', 'Disetujui')['nilai'])->toBe(1);
});

test('statistik di-cache dan otomatis dihapus saat data berubah', function () {
    $svc = app(StatistikDesa::class);
    $svc->ambil();
    expect(Cache::has('statistik.dashboard'))->toBeTrue();

    // Perubahan data tidak terlihat selama cache, tetapi cache dihapus oleh event model.
    Penduduk::factory()->create(['status' => 'hidup']);
    expect(Cache::has('statistik.dashboard'))->toBeFalse();
    expect($svc->ambil()['penduduk']['total'])->toBe(3);

    BidangTanah::factory()->persegi(103.5)->create(['luas_m2' => 100, 'nik_pemilik' => $this->budi->nik, 'wilayah_id' => $this->wilayah->id]);
    expect(Cache::has('statistik.dashboard'))->toBeFalse();
    expect($svc->ambil()['persil']['total'])->toBe(4);

    $b = BidangTanah::first();
    $svc->ambil();
    $b->delete();
    expect(Cache::has('statistik.dashboard'))->toBeFalse();
});

test('antrean kerja selalu terkini (tidak di-cache)', function () {
    $svc = app(StatistikDesa::class);
    expect($svc->antrean())->toBe(['menunggu_verifikasi' => 0, 'menunggu_persetujuan' => 0]);

    PengajuanSurat::factory()->create(['bidang_tanah_id' => $this->b1->id, 'status' => 'diajukan']);
    PengajuanSurat::factory()->create(['bidang_tanah_id' => $this->b2->id, 'status' => 'diverifikasi']);

    expect($svc->antrean())->toBe(['menunggu_verifikasi' => 1, 'menunggu_persetujuan' => 1]);
});

// ---------------------------------------------------------------- laporan

test('halaman laporan dapat dibuka admin & kades, tidak untuk warga', function () {
    foreach (['buku-tanah', 'rekap-peralihan', 'rekap-surat'] as $slug) {
        $this->actingAs($this->admin)->get(route('laporan.index', $slug))->assertOk();
        $this->actingAs($this->kades)->get(route('laporan.index', $slug))->assertOk();
        $this->actingAs(User::factory()->warga()->create())->get(route('laporan.index', $slug))->assertForbidden();
    }

    $this->actingAs($this->admin)->get('/laporan/tidak-ada')->assertNotFound();
});

test('buku tanah: data server-side, diurutkan per pemilik, filter', function () {
    $this->actingAs($this->kades);
    $url = route('laporan.data', 'buku-tanah');

    $res = $this->getJson($url.'?draw=1&start=0&length=10')->assertOk()->assertJsonPath('recordsTotal', 3);
    expect(array_column($res->json('data'), 0))->toBe(['Ani Lestari', 'Budi Santoso', 'Budi Santoso']);
    expect($res->json('data.0'))->toHaveCount(9);

    $this->getJson($url.'?draw=1&start=0&length=10&status_hak=SKT')->assertJsonPath('recordsTotal', 2);
    $this->getJson($url.'?draw=1&start=0&length=2')->assertJsonCount(2, 'data')->assertJsonPath('recordsTotal', 3);
    $this->getJson($url.'?status_hak=NGAWUR')->assertUnprocessable();
});

test('rekap peralihan & surat: filter periode dan validasi tanggal', function () {
    PeralihanHak::create(['bidang_tanah_id' => $this->b1->id, 'nik_pemilik_lama' => $this->siti->nik, 'nik_pemilik_baru' => $this->budi->nik,
        'jenis' => 'jual_beli', 'tanggal' => now()->subDays(5)->toDateString(), 'created_by' => $this->admin->id]);
    PeralihanHak::create(['bidang_tanah_id' => $this->b2->id, 'nik_pemilik_lama' => $this->siti->nik, 'nik_pemilik_baru' => $this->budi->nik,
        'jenis' => 'waris', 'tanggal' => now()->subYears(2)->toDateString(), 'created_by' => $this->admin->id]);
    PengajuanSurat::factory()->create(['bidang_tanah_id' => $this->b1->id, 'status' => 'diajukan']);

    $this->actingAs($this->admin);
    $dari = now()->subMonth()->toDateString();

    $this->getJson(route('laporan.data', 'rekap-peralihan').'?draw=1&start=0&length=10')->assertJsonPath('recordsTotal', 2);
    $this->getJson(route('laporan.data', 'rekap-peralihan')."?draw=1&start=0&length=10&dari=$dari")->assertJsonPath('recordsTotal', 1);
    $this->getJson(route('laporan.data', 'rekap-peralihan').'?jenis=waris')->assertJsonPath('recordsTotal', 1);
    $this->getJson(route('laporan.data', 'rekap-peralihan').'?dari=2026-12-31&sampai=2026-01-01')->assertUnprocessable();

    $this->getJson(route('laporan.data', 'rekap-surat')."?draw=1&start=0&length=10&dari=$dari")->assertJsonPath('recordsTotal', 1);
    $this->getJson(route('laporan.data', 'rekap-surat').'?status=ditolak')->assertJsonPath('recordsTotal', 0);
});

test('ekspor PDF ketiga laporan', function () {
    $this->actingAs($this->admin);

    foreach (['buku-tanah', 'rekap-peralihan', 'rekap-surat'] as $slug) {
        $res = $this->get(route('laporan.pdf', $slug))->assertOk();
        expect($res->headers->get('content-type'))->toContain('application/pdf')
            ->and(substr($res->getContent(), 0, 4))->toBe('%PDF');
    }
});

test('ekspor Excel: NIK tetap teks 16 digit dan isi sesuai filter', function () {
    $res = $this->actingAs($this->kades)->get(route('laporan.excel', 'buku-tanah').'?status_hak=SKT')->assertOk();

    $baris = IOFactory::load($res->baseResponse->getFile()->getPathname())->getActiveSheet()->toArray(null, true, false, false);

    expect($baris[0][0])->toBe('Pemilik')
        ->and($baris)->toHaveCount(3)                      // header + 2 persil SKT
        ->and($baris[1][0])->toBe('Ani Lestari')
        ->and($baris[1][1])->toBe('1505010101900002')      // tidak berubah menjadi 1.5E+15
        ->and($baris[2][2])->toBe('PRS-2');
});

test('ringkasan buku tanah menghitung persil, pemilik, dan luas', function () {
    $r = (new App\Reports\BukuTanah)->ringkasan([]);

    expect($r['Jumlah persil'])->toBe('3')->and($r['Jumlah pemilik'])->toBe('2')->and($r['Total luas'])->toStartWith('3.500 m²');
});

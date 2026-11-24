<?php

use App\Models\BidangTanah;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Penduduk;
use App\Models\PengajuanSurat;
use App\Models\User;
use App\Models\Wilayah;
use Database\Seeders\JenisSuratSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Skenario menyeluruh dari sudut pandang tiga peran, memakai HTTP seperti pengguna sungguhan.
 */
beforeEach(function () {
    Storage::fake('local');
    $this->seed(JenisSuratSeeder::class);

    $this->admin = User::factory()->admin()->create(['name' => 'Admin Desa', 'username' => 'admin']);
    $this->kades = User::factory()->kades()->create(['name' => 'Kepala Desa', 'username' => 'kades']);
    $this->wilayah = Wilayah::factory()->create();

    $this->penjual = Penduduk::factory()->create(['nik' => '1505010101900001', 'nama' => 'Siti Penjual']);
    $this->pembeli = Penduduk::factory()->create(['nik' => '1505010101900002', 'nama' => 'Budi Pembeli']);
    $this->wargaPembeli = User::factory()->warga()->create(['username' => 'budi', 'nik' => $this->pembeli->nik]);
});

test('ALUR 1: admin mencatat bidang tanah baru lalu peralihan hak kepada pembeli', function () {
    $this->actingAs($this->admin);

    // 1. Tambah bidang tanah (polygon persegi ±100 m) atas nama penjual
    $geojson = json_encode(BidangTanah::factory()->persegi(103.0, -1.0, 100)->make()->geojson);
    $this->postJson(route('admin.bidang-tanah.store'), [
        'no_persil' => 'PRS-100', 'nik_pemilik' => $this->penjual->nik, 'luas_m2' => 10000, 'status_hak' => 'SHM',
        'no_sertifikat' => 'SHM-00100', 'penggunaan_lahan' => 'pemukiman', 'wilayah_id' => $this->wilayah->id, 'geojson' => $geojson,
    ])->assertOk();

    $bidang = BidangTanah::firstWhere('no_persil', 'PRS-100');
    expect($bidang->nik_pemilik)->toBe($this->penjual->nik)
        ->and($bidang->luas_geometri_m2)->toBeBetween(9900, 10100);

    // 2. Muncul di API peta (bbox) dan di peta warga
    $this->getJson(route('api.bidang-tanah.geojson').'?bbox=102.99,-1.01,103.01,-0.99')->assertJsonCount(1, 'features');

    // 3. Peralihan hak jual beli + dokumen akta
    $this->postJson(route('admin.peralihan-hak.store'), [
        'bidang_tanah_id' => $bidang->id, 'nik_pemilik_lama' => $this->penjual->nik, 'nik_pemilik_baru' => $this->pembeli->nik,
        'jenis' => 'jual_beli', 'tanggal' => now()->subDay()->toDateString(), 'no_akta' => 'AKTA/77',
        'dokumen' => UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf'),
    ])->assertOk();

    expect($bidang->refresh()->nik_pemilik)->toBe($this->pembeli->nik);

    // 4. Timeline kepemilikan menampilkan riwayat; penjual tidak lagi punya bidang
    $this->get(route('admin.bidang-tanah.show', $bidang))->assertSee('Siti Penjual')->assertSee('Budi Pembeli')->assertSee('Jual Beli');
    expect($this->penjual->bidangTanah()->count())->toBe(0);

    // 5. Semua aksi di atas tercatat di log aktivitas
    $log = LogAktivitas::where('user_id', $this->admin->id)->pluck('model', 'aksi')->all();
    expect(LogAktivitas::where('model', 'BidangTanah')->where('aksi', 'tambah')->exists())->toBeTrue()
        ->and(LogAktivitas::where('model', 'PeralihanHak')->where('aksi', 'tambah')->exists())->toBeTrue()
        ->and(LogAktivitas::where('model', 'BidangTanah')->where('aksi', 'ubah')->exists())->toBeTrue();
});

test('ALUR 2: pembeli mengajukan surat, diverifikasi admin, disetujui kades, diunduh, divalidasi, lalu selesai', function () {
    $bidang = BidangTanah::factory()->persegi()->create([
        'no_persil' => 'PRS-200', 'nik_pemilik' => $this->pembeli->nik, 'wilayah_id' => $this->wilayah->id,
    ]);
    $jenis = JenisSurat::firstWhere('kode', 'SKRT');

    // Warga mengajukan
    $berkas = collect($jenis->persyaratan)->mapWithKeys(fn ($n, $i) => [$i => UploadedFile::fake()->create("b$i.pdf", 50, 'application/pdf')])->all();
    $this->actingAs($this->wargaPembeli)->postJson(route('warga.surat.store'), [
        'jenis_surat_id' => $jenis->id, 'bidang_tanah_id' => $bidang->id, 'keperluan' => 'Kelengkapan berkas jual beli', 'dokumen' => $berkas,
    ])->assertOk();

    $p = PengajuanSurat::firstOrFail();
    expect($p->status)->toBe('diajukan');
    $this->get(route('warga.surat.show', $p))->assertOk()->assertSee('Diajukan');
    $this->get(route('surat.pdf', $p))->assertNotFound();                // belum terbit

    // Kades belum melihat pengajuan yang belum diverifikasi
    $this->actingAs($this->kades)->getJson(route('kades.pengajuan.data', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertJsonPath('recordsTotal', 0);

    // Admin memeriksa berkas lalu verifikasi
    $this->actingAs($this->admin)->get(route('admin.pengajuan.show', $p))->assertOk()->assertSee('Fotokopi KTP');
    $this->get(route('dokumen-pengajuan.unduh', $p->dokumen->first()))->assertOk();
    $this->postJson(route('admin.pengajuan.verifikasi', $p))->assertOk();

    // Kades menyetujui
    $this->actingAs($this->kades)->getJson(route('kades.pengajuan.data', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertJsonPath('recordsTotal', 1);
    $this->postJson(route('kades.pengajuan.setujui', $p))->assertOk();
    $p->refresh();
    expect($p->status)->toBe('disetujui')->and($p->nomor_surat)->toMatch('#^001/SKRT/KD/[IVX]+/\d{4}$#');

    // Warga mengunduh PDF yang sah
    $pdf = $this->actingAs($this->wargaPembeli)->get(route('surat.pdf', $p))->assertOk();
    expect(substr($pdf->getContent(), 0, 4))->toBe('%PDF');

    // Siapa pun dapat memeriksa keaslian lewat QR (tanpa login), identitas disamarkan
    auth()->logout();
    $this->get(route('validasi', $p->kode_validasi))->assertSee('SURAT VALID')->assertSee('B*** P*****')->assertDontSee('Budi Pembeli');

    // Admin menandai selesai setelah surat diserahkan
    $this->actingAs($this->admin)->postJson(route('admin.pengajuan.selesai', $p))->assertOk();
    expect($p->refresh()->status)->toBe('selesai');

    // Peta warga: persil miliknya ditandai; dashboard mencerminkan surat terbit
    $this->actingAs($this->wargaPembeli)->get(route('warga.beranda'))->assertSee('Surat siap diunduh');

    // Jejak: setiap transisi status tercatat oleh pelakunya
    $oleh = LogAktivitas::where('model', 'PengajuanSurat')->where('aksi', 'ubah')->pluck('user_id')->all();
    expect($oleh)->toContain($this->admin->id)->toContain($this->kades->id);
});

test('ALUR 3: penolakan oleh kades, warga melihat alasan lalu mengajukan ulang', function () {
    $bidang = BidangTanah::factory()->persegi()->create(['nik_pemilik' => $this->pembeli->nik, 'wilayah_id' => $this->wilayah->id]);
    $jenis = JenisSurat::firstWhere('kode', 'SKTS');
    $berkas = fn () => collect($jenis->persyaratan)->mapWithKeys(fn ($n, $i) => [$i => UploadedFile::fake()->create("b$i.pdf", 50, 'application/pdf')])->all();
    $data = fn () => ['jenis_surat_id' => $jenis->id, 'bidang_tanah_id' => $bidang->id, 'keperluan' => 'Persyaratan kredit bank', 'dokumen' => $berkas()];

    $this->actingAs($this->wargaPembeli)->postJson(route('warga.surat.store'), $data())->assertOk();
    $p = PengajuanSurat::firstOrFail();

    $this->actingAs($this->admin)->postJson(route('admin.pengajuan.verifikasi', $p))->assertOk();
    $this->actingAs($this->kades)->postJson(route('kades.pengajuan.tolak', $p), ['catatan' => 'Ada keberatan dari tetangga'])->assertOk();

    $this->actingAs($this->wargaPembeli)->get(route('warga.surat.show', $p))->assertSee('Ada keberatan dari tetangga');

    // Pengajuan yang ditolak tidak lagi menghalangi pengajuan baru untuk kombinasi yang sama
    $this->postJson(route('warga.surat.store'), $data())->assertOk();
    expect(PengajuanSurat::count())->toBe(2);
});

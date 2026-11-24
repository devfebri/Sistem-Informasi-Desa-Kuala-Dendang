<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\BidangTanahController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KartuKeluargaController;
use App\Http\Controllers\PencarianController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LogAktivitasController;
use App\Http\Controllers\PengajuanSuratController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\PetaPublikController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\PendudukController;
use App\Http\Controllers\PeralihanHakController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidasiController;
use App\Http\Controllers\WargaBidangController;
use App\Http\Controllers\WargaSuratController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::model('pengajuan', \App\Models\PengajuanSurat::class);

Auth::routes(['register' => false, 'reset' => false, 'verify' => false, 'confirm' => false]);

// Halaman awal publik
Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::get('validasi', [BerandaController::class, 'cekSurat'])->middleware('throttle:30,1')->name('validasi.cari');

// Publik: validasi surat dari QR code
Route::get('validasi/{kode}', [ValidasiController::class, 'show'])->middleware('throttle:30,1')->name('validasi');

Route::middleware('auth')->group(function () {
    // Profil sendiri (semua role)
    Route::get('profil', [ProfilController::class, 'show'])->name('profil.show');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('profil/password', [ProfilController::class, 'password'])->name('profil.password');

    // PDF surat & berkas persyaratan (otorisasi per pengajuan di controller)
    Route::get('pengajuan/{pengajuan}/surat.pdf', [SuratController::class, 'pdf'])->name('surat.pdf');
    Route::get('dokumen-pengajuan/{dokumen}', [SuratController::class, 'dokumen'])->name('dokumen-pengajuan.unduh');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('peta', [PetaController::class, 'index'])->name('peta');

        Route::get('pengguna/data', [UserController::class, 'data'])->name('pengguna.data');
        Route::resource('pengguna', UserController::class)
            ->parameters(['pengguna' => 'user'])
            ->except(['create', 'show']);

        // Data kependudukan
        Route::get('wilayah/data', [WilayahController::class, 'data'])->name('wilayah.data');
        Route::resource('wilayah', WilayahController::class)->except(['create', 'show']);

        Route::get('kartu-keluarga/data', [KartuKeluargaController::class, 'data'])->name('kartu-keluarga.data');
        Route::resource('kartu-keluarga', KartuKeluargaController::class)
            ->parameters(['kartu-keluarga' => 'kartuKeluarga'])
            ->except(['create', 'show']);

        Route::get('penduduk/data', [PendudukController::class, 'data'])->name('penduduk.data');
        Route::resource('penduduk', PendudukController::class)->except(['create', 'show']);

        // Data pertanahan
        Route::get('bidang-tanah/data', [BidangTanahController::class, 'data'])->name('bidang-tanah.data');
        Route::resource('bidang-tanah', BidangTanahController::class)
            ->parameters(['bidang-tanah' => 'bidangTanah']);

        // Peralihan hak (riwayat bersifat permanen: tanpa ubah/hapus)
        Route::get('peralihan-hak/data', [PeralihanHakController::class, 'data'])->name('peralihan-hak.data');
        Route::get('peralihan-hak/{peralihanHak}/dokumen', [PeralihanHakController::class, 'dokumen'])->name('peralihan-hak.dokumen');
        Route::resource('peralihan-hak', PeralihanHakController::class)
            ->parameters(['peralihan-hak' => 'peralihanHak'])
            ->only(['index', 'store']);

        // Layanan surat: verifikasi admin
        Route::get('pengajuan/data', [PengajuanSuratController::class, 'data'])->name('pengajuan.data');
        Route::get('pengajuan', [PengajuanSuratController::class, 'index'])->name('pengajuan.index');
        Route::get('pengajuan/{pengajuan}', [PengajuanSuratController::class, 'show'])->name('pengajuan.show');
        Route::post('pengajuan/{pengajuan}/verifikasi', [PengajuanSuratController::class, 'verifikasi'])->name('pengajuan.verifikasi');
        Route::post('pengajuan/{pengajuan}/tolak', [PengajuanSuratController::class, 'tolak'])->name('pengajuan.tolak');
        Route::post('pengajuan/{pengajuan}/selesai', [PengajuanSuratController::class, 'selesai'])->name('pengajuan.selesai');

        // Log aktivitas
        Route::get('log-aktivitas/data', [LogAktivitasController::class, 'data'])->name('log.data');
        Route::get('log-aktivitas', [LogAktivitasController::class, 'index'])->name('log.index');

        // Select2 AJAX
        Route::get('cari/bidang-tanah', [PencarianController::class, 'bidangTanah'])->name('cari.bidang-tanah');
        Route::get('cari/penduduk', [PencarianController::class, 'penduduk'])->name('cari.penduduk');
        Route::get('cari/kartu-keluarga', [PencarianController::class, 'kartuKeluarga'])->name('cari.kk');
    });

    // Laporan (pratinjau, PDF, Excel): admin & kades
    Route::middleware('role:admin,kades')->prefix('laporan')->name('laporan.')
        ->where(['laporan' => 'buku-tanah|rekap-peralihan|rekap-surat'])->group(function () {
            Route::get('{laporan}', [LaporanController::class, 'index'])->name('index');
            Route::get('{laporan}/data', [LaporanController::class, 'data'])->name('data');
            Route::get('{laporan}/pdf', [LaporanController::class, 'pdf'])->name('pdf');
            Route::get('{laporan}/excel', [LaporanController::class, 'excel'])->name('excel');
        });

    // API peta (sesi login; admin & kades). Dimuat per bounding box.
    Route::middleware('role:admin,kades')->prefix('api')->name('api.')->group(function () {
        Route::get('bidang-tanah/geojson', [BidangTanahController::class, 'geojson'])->name('bidang-tanah.geojson');
        Route::get('bidang-tanah/cari', [BidangTanahController::class, 'cari'])->name('bidang-tanah.cari');
        Route::get('bidang-tanah/{bidangTanah}/popup', [BidangTanahController::class, 'popup'])->name('bidang-tanah.popup');
        Route::get('wilayah/geojson', [BidangTanahController::class, 'wilayahGeojson'])->name('wilayah.geojson');
    });

    // Batas dusun (tidak sensitif) juga untuk peta warga
    Route::get('api/wilayah/publik', [BidangTanahController::class, 'wilayahGeojson'])->middleware('role:warga')->name('api.wilayah.publik');

    Route::middleware('role:kades')->prefix('kades')->name('kades.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'kades'])->name('dashboard');
        Route::get('peta', [PetaController::class, 'index'])->name('peta');

        Route::get('pengajuan/data', [PengajuanSuratController::class, 'data'])->name('pengajuan.data');
        Route::get('pengajuan', [PengajuanSuratController::class, 'index'])->name('pengajuan.index');
        Route::get('pengajuan/{pengajuan}', [PengajuanSuratController::class, 'show'])->name('pengajuan.show');
        Route::post('pengajuan/{pengajuan}/setujui', [PengajuanSuratController::class, 'setujui'])->name('pengajuan.setujui');
        Route::post('pengajuan/{pengajuan}/tolak', [PengajuanSuratController::class, 'tolak'])->name('pengajuan.tolak');
    });

    Route::middleware('role:warga')->prefix('warga')->name('warga.')->group(function () {
        Route::get('beranda', [DashboardController::class, 'warga'])->name('beranda');

        // Peta untuk warga (data pemilik disamarkan, tanpa NIK)
        Route::get('peta', [PetaPublikController::class, 'index'])->name('peta');
        Route::get('api/geojson', [PetaPublikController::class, 'geojson'])->name('api.geojson');
        Route::get('api/cari', [PetaPublikController::class, 'cari'])->name('api.cari');
        Route::get('api/bidang-tanah/{bidangTanah}/popup', [PetaPublikController::class, 'popup'])->name('api.popup');

        Route::get('bidang-saya', [WargaBidangController::class, 'index'])->name('bidang');
        Route::get('bidang-saya/data', [WargaBidangController::class, 'data'])->name('bidang.data');

        Route::get('surat/data', [WargaSuratController::class, 'data'])->name('surat.data');
        Route::get('surat', [WargaSuratController::class, 'index'])->name('surat.index');
        Route::post('surat', [WargaSuratController::class, 'store'])->name('surat.store');
        Route::get('surat/{pengajuan}', [WargaSuratController::class, 'show'])->name('surat.show');
    });
});

<?php

use App\Models\BidangTanah;
use App\Models\Penduduk;
use App\Models\User;
use Database\Seeders\JenisSuratSeeder;

test('tamu melihat halaman awal publik dengan tombol masuk', function () {
    $this->get('/')->assertOk()
        ->assertSee('Sistem Informasi Desa')
        ->assertSee('Cek keaslian surat')
        ->assertSee(route('login'), false)
        ->assertDontSee('Ke Dashboard');
});

test('halaman awal menampilkan angka agregat tanpa data pribadi', function () {
    $this->seed(JenisSuratSeeder::class);
    $p = Penduduk::factory()->create(['nama' => 'Rahasia Pribadi', 'nik' => '1505010101900099', 'status' => 'hidup']);
    BidangTanah::factory()->persegi()->create(['nik_pemilik' => $p->nik, 'no_persil' => 'PRS-PRIBADI', 'luas_m2' => 5000]);

    $this->get('/')->assertOk()
        ->assertSee('Penduduk')->assertSee('Bidang Tanah')->assertSee('Surat Keterangan Tanah')
        ->assertDontSee('Rahasia Pribadi')->assertDontSee('1505010101900099')->assertDontSee('PRS-PRIBADI');
});

test('pengguna yang sudah login melihat tombol ke dashboard sesuai role', function () {
    $this->actingAs(User::factory()->kades()->create())->get('/')->assertOk()
        ->assertSee('Ke Dashboard')->assertSee(route('kades.dashboard'), false);

    $this->actingAs(User::factory()->warga()->create())->get('/')->assertSee(route('warga.beranda'), false);
});

test('cek keaslian surat: kode valid diarahkan ke halaman validasi (huruf besar)', function () {
    $this->get(route('validasi.cari', ['kode' => 'ab12cd34ef56']))->assertRedirect(route('validasi', 'AB12CD34EF56'));
});

test('cek keaslian surat: kode kosong/tidak valid kembali ke beranda dengan pesan', function () {
    $this->get(route('validasi.cari', ['kode' => '']))->assertRedirect(url('/').'#cek-surat')->assertSessionHasErrors('kode');
    $this->get(route('validasi.cari', ['kode' => 'ab-12!']))->assertSessionHasErrors('kode');
    $this->get(route('validasi.cari', ['kode' => 'ABC']))->assertSessionHasErrors('kode');

    $this->followingRedirects()->get(route('validasi.cari', ['kode' => 'ab']))->assertSee('Kode validasi terdiri dari 12 karakter');
});

test('halaman login punya tautan kembali ke beranda', function () {
    $this->get(route('login'))->assertOk()->assertSee('Kembali ke beranda');
});

<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use App\Services\StatistikDesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** Halaman awal publik. Hanya menampilkan angka agregat (tanpa data pribadi). */
class BerandaController extends Controller
{
    public function index(StatistikDesa $statistik)
    {
        $stat = $statistik->ambil();

        return view('landing', [
            'angka' => [
                'penduduk' => $stat['penduduk']['total'],
                'kk' => $stat['kk'],
                'persil' => $stat['persil']['total'],
                'luas_ha' => $stat['persil']['luas_m2'] / 10000,
            ],
            'jenisSurat' => JenisSurat::aktif(),
        ]);
    }

    /** Formulir "Cek keaslian surat": mengarahkan ke /validasi/{kode}. */
    public function cekSurat(Request $request): RedirectResponse
    {
        $v = Validator::make(
            $request->only('kode'),
            ['kode' => ['required', 'string', 'alpha_num', 'between:6,20']],
            ['kode.alpha_num' => 'Kode hanya boleh berisi huruf dan angka.', 'kode.between' => 'Kode validasi terdiri dari 12 karakter, tercetak di bawah QR code pada surat.'],
            ['kode' => 'kode validasi'],
        );

        if ($v->fails()) {
            return redirect(url('/').'#cek-surat')->withErrors($v)->withInput();
        }

        return redirect()->route('validasi', strtoupper($v->validated()['kode']));
    }
}

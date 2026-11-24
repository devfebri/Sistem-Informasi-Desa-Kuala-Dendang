<?php

namespace App\Http\Controllers;

use App\Models\BidangTanah;
use App\Models\Wilayah;

/** Peta lengkap untuk petugas (admin & kades). Warga memakai PetaPublikController. */
class PetaController extends Controller
{
    public function index()
    {
        return view('peta.index', [
            'publik' => false,
            'wilayah' => Wilayah::options(),
            'statusHak' => BidangTanah::STATUS_HAK,
            'penggunaan' => BidangTanah::PENGGUNAAN,
            'urls' => [
                'geojson' => route('api.bidang-tanah.geojson'),
                'wilayah' => route('api.wilayah.geojson'),
                'popup' => route('api.bidang-tanah.popup', ['bidangTanah' => '__ID__']),
                'cari' => route('api.bidang-tanah.cari'),
            ],
        ]);
    }
}

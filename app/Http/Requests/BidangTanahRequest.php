<?php

namespace App\Http\Requests;

use App\Models\BidangTanah;
use App\Support\Geo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BidangTanahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $bidang = $this->route('bidangTanah');

        return [
            'no_persil' => ['required', 'string', 'max:50', Rule::unique('bidang_tanah', 'no_persil')->ignore($bidang?->id)],
            'nik_pemilik' => ['required', 'digits:16', Rule::exists('penduduk', 'nik')],
            'luas_m2' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'status_hak' => ['required', Rule::in(BidangTanah::STATUS_HAK)],
            'no_sertifikat' => ['nullable', 'required_if:status_hak,SHM,HGB', 'string', 'max:100'],
            'penggunaan_lahan' => ['required', Rule::in(BidangTanah::PENGGUNAAN)],
            'batas_utara' => ['nullable', 'string', 'max:255'],
            'batas_selatan' => ['nullable', 'string', 'max:255'],
            'batas_timur' => ['nullable', 'string', 'max:255'],
            'batas_barat' => ['nullable', 'string', 'max:255'],
            'wilayah_id' => ['required', Rule::exists('wilayah', 'id')],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'geojson' => ['required', 'string', function ($attr, $value, $fail) {
                if (Geo::normalizePolygon($value) === null) {
                    $fail('Polygon bidang tanah belum digambar atau tidak valid.');
                }
            }],
        ];
    }

    public function attributes(): array
    {
        return [
            'no_persil' => 'No. persil',
            'nik_pemilik' => 'pemilik',
            'luas_m2' => 'luas',
            'status_hak' => 'status hak',
            'no_sertifikat' => 'No. sertifikat',
            'penggunaan_lahan' => 'penggunaan lahan',
            'wilayah_id' => 'dusun/RT',
            'geojson' => 'polygon',
        ];
    }

    /** Data siap simpan: geometri dinormalisasi, luas geometri & bbox dihitung di server. */
    public function payload(): array
    {
        $data = $this->validated();
        $geom = Geo::normalizePolygon($data['geojson']);
        [$minLng, $minLat, $maxLng, $maxLat] = Geo::bbox($geom);

        return array_merge($data, [
            'geojson' => $geom,
            'luas_geometri_m2' => round(Geo::area($geom), 2),
            'min_lng' => $minLng, 'min_lat' => $minLat, 'max_lng' => $maxLng, 'max_lat' => $maxLat,
        ]);
    }
}

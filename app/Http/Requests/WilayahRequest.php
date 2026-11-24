<?php

namespace App\Http\Requests;

use App\Support\Geo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WilayahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Normalisasi RT/RW menjadi 3 digit ("1" -> "001").
        foreach (['rt', 'rw'] as $f) {
            if (ctype_digit((string) $this->input($f)) && strlen((string) $this->input($f)) <= 3) {
                $this->merge([$f => str_pad((string) $this->input($f), 3, '0', STR_PAD_LEFT)]);
            }
        }
    }

    public function rules(): array
    {
        $wilayah = $this->route('wilayah');

        return [
            'nama_dusun' => ['required', 'string', 'max:100'],
            'rt' => ['required', 'digits_between:1,3'],
            'rw' => [
                'required', 'digits_between:1,3',
                Rule::unique('wilayah', 'rw')
                    ->where('nama_dusun', $this->input('nama_dusun'))
                    ->where('rt', $this->input('rt'))
                    ->ignore($wilayah?->id),
            ],
            'geojson' => ['nullable', 'string', function ($attr, $value, $fail) {
                if (filled($value) && Geo::normalizePolygon($value) === null) {
                    $fail('Batas wilayah harus berupa GeoJSON Polygon yang valid.');
                }
            }],
        ];
    }

    /** Data simpan: geojson dinormalisasi menjadi geometry Polygon (atau null). */
    public function payload(): array
    {
        $data = $this->validated();
        $data['geojson'] = filled($data['geojson'] ?? null) ? Geo::normalizePolygon($data['geojson']) : null;

        return $data;
    }

    public function messages(): array
    {
        return ['rw.unique' => 'Kombinasi dusun, RT, dan RW ini sudah ada.'];
    }

    public function attributes(): array
    {
        return ['nama_dusun' => 'nama dusun', 'rt' => 'RT', 'rw' => 'RW', 'geojson' => 'batas wilayah'];
    }
}

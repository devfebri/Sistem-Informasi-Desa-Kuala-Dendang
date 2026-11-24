<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KartuKeluargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $kk = $this->route('kartuKeluarga');

        return [
            'no_kk' => ['required', 'digits:16', Rule::unique('kartu_keluarga', 'no_kk')->ignore($kk?->id)],
            'nik_kepala_keluarga' => ['nullable', 'digits:16', Rule::exists('penduduk', 'nik')],
            'alamat' => ['required', 'string', 'max:255'],
            'wilayah_id' => ['required', Rule::exists('wilayah', 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'no_kk' => 'No. KK',
            'nik_kepala_keluarga' => 'kepala keluarga',
            'alamat' => 'alamat',
            'wilayah_id' => 'dusun/RT',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Penduduk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PendudukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $penduduk = $this->route('penduduk');

        return [
            // NIK tidak dapat diubah setelah dibuat (dirujuk akun, bidang tanah, dll).
            'nik' => $penduduk ? ['nullable'] : ['required', 'digits:16', Rule::unique('penduduk', 'nik')],
            'no_kk' => ['required', 'digits:16', Rule::exists('kartu_keluarga', 'no_kk')],
            'nama' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', Rule::in(array_keys(Penduduk::JENIS_KELAMIN))],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'agama' => ['required', Rule::in(Penduduk::AGAMA)],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'status_perkawinan' => ['required', Rule::in(Penduduk::STATUS_PERKAWINAN)],
            'hubungan_keluarga' => ['required', Rule::in(Penduduk::HUBUNGAN_KELUARGA)],
            'alamat' => ['nullable', 'string', 'max:255'],
            'wilayah_id' => ['required', Rule::exists('wilayah', 'id')],
            'status' => ['required', Rule::in(array_keys(Penduduk::STATUS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'nik' => 'NIK',
            'no_kk' => 'No. KK',
            'nama' => 'nama',
            'jenis_kelamin' => 'jenis kelamin',
            'tempat_lahir' => 'tempat lahir',
            'tanggal_lahir' => 'tanggal lahir',
            'status_perkawinan' => 'status perkawinan',
            'hubungan_keluarga' => 'hubungan keluarga',
            'wilayah_id' => 'dusun/RT',
        ];
    }
}

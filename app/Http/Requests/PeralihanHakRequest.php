<?php

namespace App\Http\Requests;

use App\Models\Penduduk;
use App\Models\PeralihanHak;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeralihanHakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'bidang_tanah_id' => ['required', Rule::exists('bidang_tanah', 'id')],
            'nik_pemilik_lama' => ['required', 'digits:16'],
            'nik_pemilik_baru' => [
                'required', 'digits:16', 'different:nik_pemilik_lama', Rule::exists('penduduk', 'nik'),
                function ($attr, $value, $fail) {
                    $status = Penduduk::where('nik', $value)->value('status');
                    if ($status !== null && $status !== 'hidup') {
                        $fail('Pemilik baru harus berstatus hidup.');
                    }
                },
            ],
            'jenis' => ['required', Rule::in(array_keys(PeralihanHak::JENIS))],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'no_akta' => ['nullable', 'string', 'max:100'],
            'dokumen' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nik_pemilik_baru.different' => 'Pemilik baru tidak boleh sama dengan pemilik lama.',
            'dokumen.max' => 'Ukuran dokumen maksimal 5 MB.',
            'dokumen.mimes' => 'Dokumen harus berformat PDF, JPG, atau PNG.',
        ];
    }

    public function attributes(): array
    {
        return [
            'bidang_tanah_id' => 'bidang tanah',
            'nik_pemilik_lama' => 'pemilik lama',
            'nik_pemilik_baru' => 'pemilik baru',
            'jenis' => 'jenis peralihan',
            'tanggal' => 'tanggal',
            'no_akta' => 'No. akta',
            'dokumen' => 'dokumen',
        ];
    }
}

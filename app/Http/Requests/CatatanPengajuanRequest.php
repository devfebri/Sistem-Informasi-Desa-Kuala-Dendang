<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Alasan penolakan pengajuan surat (wajib). */
class CatatanPengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isKades();
    }

    public function rules(): array
    {
        return ['catatan' => ['required', 'string', 'min:5', 'max:1000']];
    }

    public function attributes(): array
    {
        return ['catatan' => 'alasan penolakan'];
    }
}

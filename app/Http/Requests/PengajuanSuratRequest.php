<?php

namespace App\Http\Requests;

use App\Models\BidangTanah;
use App\Models\JenisSurat;
use App\Models\Penduduk;
use App\Models\PengajuanSurat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Pengajuan surat oleh warga. Jumlah berkas wajib mengikuti persyaratan jenis surat. */
class PengajuanSuratRequest extends FormRequest
{
    private ?JenisSurat $jenis = null;

    public function authorize(): bool
    {
        return $this->user()?->isWarga() ?? false;
    }

    public function persyaratan(): array
    {
        $this->jenis ??= JenisSurat::query()->where('is_active', true)->find($this->input('jenis_surat_id'));

        return $this->jenis?->persyaratan ?? [];
    }

    public function rules(): array
    {
        $rules = [
            'jenis_surat_id' => ['required', Rule::exists('jenis_surat', 'id')->where('is_active', true)],
            'bidang_tanah_id' => ['required', Rule::exists('bidang_tanah', 'id')],
            'keperluan' => ['required', 'string', 'min:5', 'max:500'],
        ];

        foreach (array_keys($this->persyaratan()) as $i) {
            $rules["dokumen.$i"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attr = [
            'jenis_surat_id' => 'jenis surat',
            'bidang_tanah_id' => 'bidang tanah',
            'keperluan' => 'keperluan',
        ];
        foreach ($this->persyaratan() as $i => $nama) {
            $attr["dokumen.$i"] = $nama;
        }

        return $attr;
    }

    public function messages(): array
    {
        return [
            'dokumen.*.max' => ':attribute maksimal 5 MB.',
            'dokumen.*.mimes' => ':attribute harus berformat PDF, JPG, atau PNG.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $nik = $this->user()->nik;
            $pemohon = $nik ? Penduduk::where('nik', $nik)->first() : null;

            if (! $pemohon || $pemohon->status !== 'hidup') {
                $v->errors()->add('bidang_tanah_id', 'Akun Anda belum terhubung dengan data penduduk yang aktif. Hubungi admin desa.');

                return;
            }

            $bidang = BidangTanah::find($this->input('bidang_tanah_id'));
            if (! $bidang || $bidang->nik_pemilik !== $nik) {
                $v->errors()->add('bidang_tanah_id', 'Bidang tanah ini bukan milik Anda.');

                return;
            }

            $ganda = PengajuanSurat::query()
                ->where('nik_pemohon', $nik)
                ->where('bidang_tanah_id', $bidang->id)
                ->where('jenis_surat_id', $this->input('jenis_surat_id'))
                ->whereIn('status', PengajuanSurat::AKTIF)
                ->exists();

            if ($ganda) {
                $v->errors()->add('jenis_surat_id', 'Anda sudah memiliki pengajuan yang sama dan masih diproses.');
            }
        }];
    }
}

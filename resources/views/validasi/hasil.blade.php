<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validasi Surat — {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
<div class="login-wrap">
    <div class="card login-card shadow" style="max-width:480px">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <div class="small text-muted">Pemerintah Desa {{ config('desa.nama') }}</div>
                <h5 class="mb-0">Validasi Keaslian Surat</h5>
            </div>

            @if ($p)
                <div class="alert alert-success text-center mb-3">
                    <i class="bi bi-patch-check-fill fs-3 d-block"></i>
                    <strong>SURAT VALID</strong><br>
                    <small>Surat ini diterbitkan secara resmi oleh Pemerintah Desa {{ config('desa.nama') }}.</small>
                </div>
                <dl class="row mb-0 small">
                    <dt class="col-5">Nomor surat</dt><dd class="col-7">{{ $p->nomor_surat }}</dd>
                    <dt class="col-5">Jenis surat</dt><dd class="col-7">{{ $p->jenisSurat?->nama }}</dd>
                    <dt class="col-5">Atas nama</dt><dd class="col-7">{{ $namaSamar }}</dd>
                    <dt class="col-5">No. persil</dt><dd class="col-7">{{ $p->bidangTanah?->no_persil }}</dd>
                    <dt class="col-5">Tanggal terbit</dt><dd class="col-7">{{ $p->approved_at?->translatedFormat('d F Y') }}</dd>
                    <dt class="col-5">Ditandatangani</dt><dd class="col-7">{{ $p->penyetuju?->name }} ({{ config('desa.jabatan_kades') }})</dd>
                </dl>
                <p class="text-muted small mt-3 mb-0">Nama disamarkan demi privasi. Cocokkan nomor surat dan nomor persil dengan dokumen yang Anda terima.</p>
            @else
                <div class="alert alert-danger text-center mb-0">
                    <i class="bi bi-x-octagon-fill fs-3 d-block"></i>
                    <strong>SURAT TIDAK DITEMUKAN</strong><br>
                    <small>Kode <code>{{ $kode }}</code> tidak terdaftar atau surat belum/tidak diterbitkan. Waspadai pemalsuan dokumen.</small>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>

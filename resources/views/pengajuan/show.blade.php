@extends('layouts.app')
@section('title', 'Detail Pengajuan Surat')
@php
    $indexRoute = $peran === 'warga' ? 'warga.surat.index' : "$peran.pengajuan.index";
    $langkah = [
        ['Diajukan', $p->created_at, null],
        ['Diverifikasi admin', $p->verified_at, $p->verifikator?->name],
        ['Disetujui Kepala Desa', $p->approved_at, $p->penyetuju?->name],
        ['Selesai', $p->completed_at, null],
    ];
@endphp
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}">Pengajuan Surat</a></li>
    <li class="breadcrumb-item active">{{ $p->no_registrasi }}</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">{{ $p->no_registrasi }}</span>
                {!! $p->statusBadge() !!}
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Jenis surat</dt><dd class="col-sm-8">{{ $p->jenisSurat?->nama }}</dd>
                    <dt class="col-sm-4">Pemohon</dt>
                    <dd class="col-sm-8">{{ $p->pemohon?->nama ?? $p->nik_pemohon }}<br><small class="text-muted">NIK {{ $p->nik_pemohon }}</small></dd>
                    <dt class="col-sm-4">Bidang tanah</dt>
                    <dd class="col-sm-8">
                        @if ($peran === 'admin')
                            <a href="{{ route('admin.bidang-tanah.show', $p->bidang_tanah_id) }}">{{ $p->bidangTanah?->no_persil }}</a>
                        @else
                            {{ $p->bidangTanah?->no_persil }}
                        @endif
                        <small class="text-muted">· {{ number_format($p->bidangTanah?->luas_m2 ?? 0, 0, ',', '.') }} m²</small>
                    </dd>
                    <dt class="col-sm-4">Keperluan</dt><dd class="col-sm-8">{{ $p->keperluan }}</dd>
                    @if ($p->nomor_surat)
                        <dt class="col-sm-4">Nomor surat</dt><dd class="col-sm-8"><strong>{{ $p->nomor_surat }}</strong></dd>
                    @endif
                </dl>

                @if ($p->status === 'ditolak')
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong>Ditolak</strong> oleh {{ $p->penolak?->name ?? '-' }} pada {{ $p->rejected_at?->format('d-m-Y H:i') }}.<br>
                        Alasan: {{ $p->catatan }}
                    </div>
                @endif
            </div>

            <div class="card-footer d-flex flex-wrap gap-2 justify-content-end">
                @if ($p->suratTerbit())
                    <a href="{{ route('surat.pdf', $p) }}" target="_blank" class="btn btn-success"><i class="bi bi-file-earmark-pdf me-1"></i>Lihat / Unduh Surat (PDF)</a>
                @endif

                @if ($peran === 'admin' && $p->status === 'diajukan')
                    <button class="btn btn-danger btn-aksi" data-url="{{ route('admin.pengajuan.tolak', $p) }}" data-judul="Tolak pengajuan?" data-catatan="1"><i class="bi bi-x-circle me-1"></i>Tolak</button>
                    <button class="btn btn-primary btn-aksi" data-url="{{ route('admin.pengajuan.verifikasi', $p) }}" data-judul="Verifikasi pengajuan?" data-teks="Berkas persyaratan sudah diperiksa dan diteruskan ke Kepala Desa."><i class="bi bi-check2-circle me-1"></i>Verifikasi</button>
                @endif
                @if ($peran === 'admin' && $p->status === 'disetujui')
                    <button class="btn btn-primary btn-aksi" data-url="{{ route('admin.pengajuan.selesai', $p) }}" data-judul="Tandai selesai?" data-teks="Surat sudah diserahkan kepada pemohon."><i class="bi bi-check2-all me-1"></i>Tandai Selesai</button>
                @endif
                @if ($peran === 'kades' && $p->status === 'diverifikasi')
                    <button class="btn btn-danger btn-aksi" data-url="{{ route('kades.pengajuan.tolak', $p) }}" data-judul="Tolak pengajuan?" data-catatan="1"><i class="bi bi-x-circle me-1"></i>Tolak</button>
                    <button class="btn btn-success btn-aksi" data-url="{{ route('kades.pengajuan.setujui', $p) }}" data-judul="Setujui & terbitkan surat?" data-teks="Nomor surat dan kode validasi (QR) akan dibuat otomatis."><i class="bi bi-patch-check me-1"></i>Setujui &amp; Terbitkan</button>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header fw-semibold">Berkas Persyaratan</div>
            <ul class="list-group list-group-flush">
                @forelse ($p->dokumen as $d)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-paperclip me-1"></i>{{ $d->nama_dokumen }}</span>
                        <a href="{{ route('dokumen-pengajuan.unduh', $d) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Lihat</a>
                    </li>
                @empty
                    <li class="list-group-item text-muted small">Tidak ada berkas terlampir.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header fw-semibold">Status Pengajuan</div>
            <div class="card-body">
                <ul class="timeline list-unstyled mb-0">
                    @foreach ($langkah as [$nama, $waktu, $oleh])
                        <li class="timeline-item {{ $waktu ? 'current' : '' }}">
                            <div class="{{ $waktu ? 'fw-semibold' : 'text-muted' }}">{{ $nama }}</div>
                            <small class="text-muted">{{ $waktu ? $waktu->format('d-m-Y H:i').($oleh ? ' · '.$oleh : '') : 'Menunggu' }}</small>
                        </li>
                    @endforeach
                    @if ($p->status === 'ditolak')
                        <li class="timeline-item">
                            <div class="fw-semibold text-danger">Ditolak</div>
                            <small class="text-muted">{{ $p->rejected_at?->format('d-m-Y H:i') }}</small>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.btn-aksi').on('click', function () {
                const $b = $(this);
                const perluCatatan = !!$b.data('catatan');

                Swal.fire({
                    title: $b.data('judul'),
                    text: perluCatatan ? undefined : $b.data('teks'),
                    icon: perluCatatan ? 'warning' : 'question',
                    input: perluCatatan ? 'textarea' : undefined,
                    inputLabel: perluCatatan ? 'Alasan penolakan (wajib)' : undefined,
                    inputAttributes: { maxlength: 1000 },
                    inputValidator: perluCatatan ? (v) => (!v || v.trim().length < 5 ? 'Tulis alasan minimal 5 karakter.' : undefined) : undefined,
                    showCancelButton: true,
                    confirmButtonText: 'Ya, lanjutkan',
                    cancelButtonText: 'Batal',
                }).then((r) => {
                    if (!r.isConfirmed) return;
                    setLoading($('.btn-aksi'), true);
                    $.post($b.data('url'), perluCatatan ? { catatan: r.value } : {})
                        .done((res) => { toastr.success(res.message); setTimeout(() => location.reload(), 900); })
                        .fail((xhr) => {
                            setLoading($('.btn-aksi'), false);
                            toastr.error(xhr.responseJSON?.message || xhr.responseJSON?.errors?.catatan?.[0] || 'Aksi gagal.');
                        });
                });
            });
        });
    </script>
@endpush

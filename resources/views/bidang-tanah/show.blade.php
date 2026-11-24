@extends('layouts.app')
@section('title', 'Detail Bidang Tanah')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.bidang-tanah.index') }}">Bidang Tanah</a></li>
    <li class="breadcrumb-item active">{{ $b->no_persil }}</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header fw-semibold">Persil {{ $b->no_persil }}</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Pemilik</dt><dd class="col-7">{{ $b->pemilik?->nama ?? '-' }}<br><small class="text-muted">{{ $b->nik_pemilik }}</small></dd>
                    <dt class="col-5">Alamat pemilik</dt><dd class="col-7">{{ $b->pemilik?->alamat ?? '-' }}</dd>
                    <dt class="col-5">Luas tercatat</dt><dd class="col-7">{{ number_format($b->luas_m2, 2, ',', '.') }} m²</dd>
                    <dt class="col-5">Luas peta</dt><dd class="col-7">{{ $b->luas_geometri_m2 !== null ? number_format($b->luas_geometri_m2, 2, ',', '.').' m²' : '-' }}</dd>
                    <dt class="col-5">Status hak</dt><dd class="col-7">{{ $b->status_hak }}{{ $b->no_sertifikat ? ' · '.$b->no_sertifikat : '' }}</dd>
                    <dt class="col-5">Penggunaan</dt><dd class="col-7">{{ ucfirst($b->penggunaan_lahan) }}</dd>
                    <dt class="col-5">Dusun/RT</dt><dd class="col-7">{{ $b->wilayah?->label ?? '-' }}</dd>
                    <dt class="col-5">Batas</dt>
                    <dd class="col-7 mb-0">
                        U: {{ $b->batas_utara ?: '-' }}<br>S: {{ $b->batas_selatan ?: '-' }}<br>
                        T: {{ $b->batas_timur ?: '-' }}<br>B: {{ $b->batas_barat ?: '-' }}
                    </dd>
                </dl>
                @if ($b->keterangan)<hr><p class="mb-0 small">{{ $b->keterangan }}</p>@endif
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.bidang-tanah.edit', $b) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square me-1"></i>Ubah</a>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Riwayat Kepemilikan</span>
                <a href="{{ route('admin.peralihan-hak.index', ['bidang_tanah_id' => $b->id]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-left-right me-1"></i>Catat Peralihan
                </a>
            </div>
            <div class="card-body">
                <ul class="timeline list-unstyled mb-0">
                    <li class="timeline-item current">
                        <div class="fw-semibold">{{ $b->pemilik?->nama ?? $b->nik_pemilik }} <span class="badge text-bg-success">Pemilik saat ini</span></div>
                        <small class="text-muted">NIK {{ $b->nik_pemilik }}</small>
                    </li>
                    @forelse ($riwayat as $r)
                        <li class="timeline-item">
                            <div class="small text-muted">{{ $r->tanggal->translatedFormat('d F Y') }} · <span class="badge text-bg-secondary">{{ $r->jenisLabel() }}</span></div>
                            <div>
                                <strong>{{ $r->pemilikLama?->nama ?? $r->nik_pemilik_lama }}</strong>
                                <i class="bi bi-arrow-right mx-1"></i>
                                <strong>{{ $r->pemilikBaru?->nama ?? $r->nik_pemilik_baru }}</strong>
                            </div>
                            <div class="small text-muted">
                                @if ($r->no_akta) Akta: {{ $r->no_akta }} · @endif
                                Dicatat oleh {{ $r->pencatat?->name ?? '-' }}
                                @if ($r->dokumen)
                                    · <a href="{{ route('admin.peralihan-hak.dokumen', $r) }}" target="_blank"><i class="bi bi-file-earmark-text"></i> Dokumen</a>
                                @endif
                            </div>
                            @if ($r->keterangan)<div class="small">{{ $r->keterangan }}</div>@endif
                        </li>
                    @empty
                        <li class="timeline-item text-muted small">Belum ada peralihan hak yang tercatat.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-body"><div id="peta" class="peta"></div></div></div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/peta.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Peta.petaStatis({
                el: 'peta',
                pusat: @json(config('desa.peta.pusat')),
                zoom: @json(config('desa.peta.zoom')),
                geometry: @json($b->geojson),
            });
        });
    </script>
@endpush

@extends('layouts.app')
@section('title', 'Beranda Warga')

@section('content')
@if (! $penduduk)
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>Akun Anda belum terhubung dengan data kependudukan. Silakan hubungi admin desa.</div>
@else
    <div class="mb-3">
        <h5 class="mb-0">Selamat datang, {{ $penduduk->nama }}</h5>
        <div class="text-muted small">NIK {{ $penduduk->nik }}</div>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Bidang tanah saya</div>
            <div class="fs-3 fw-semibold">{{ $jumlahBidang }}</div>
            <a href="{{ route('warga.bidang') }}" class="small">Lihat daftar</a>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Pengajuan diproses</div>
            <div class="fs-3 fw-semibold">{{ $diproses }}</div>
            <a href="{{ route('warga.surat.index') }}" class="small">Lacak status</a>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Surat siap diunduh</div>
            <div class="fs-3 fw-semibold">{{ $suratTerbit }}</div>
            <a href="{{ route('warga.surat.index') }}" class="small">Unduh surat</a>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Peta desa</div>
            <div class="fs-3 fw-semibold"><i class="bi bi-map"></i></div>
            <a href="{{ route('warga.peta') }}" class="small">Buka peta</a>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold">Pengajuan Terakhir</div>
    <ul class="list-group list-group-flush">
        @forelse ($terakhir as $p)
            <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1">
                <a href="{{ route('warga.surat.show', $p) }}" class="text-decoration-none">
                    <strong>{{ $p->no_registrasi }}</strong> · {{ $p->jenisSurat?->nama }}
                    <span class="text-muted small">· {{ $p->created_at->format('d-m-Y') }}</span>
                </a>
                {!! $p->statusBadge() !!}
            </li>
        @empty
            <li class="list-group-item text-muted small">Belum ada pengajuan surat. <a href="{{ route('warga.surat.index') }}">Ajukan sekarang</a>.</li>
        @endforelse
    </ul>
</div>
@endsection

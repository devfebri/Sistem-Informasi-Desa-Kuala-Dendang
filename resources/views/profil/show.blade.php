@extends('layouts.app')
@section('title', 'Profil Saya')
@section('breadcrumb')
    <li class="breadcrumb-item active">Profil</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Akun</div>
            <div class="card-body">
                <form id="formProfil" action="{{ route('profil.update') }}" novalidate>
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Nama lengkap</label>
                        <input type="text" name="name" class="form-control" value="{{ $user->name }}">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Username</label><input type="text" class="form-control bg-light" value="{{ $user->username }}" readonly></div>
                        <div class="col-6 mb-3"><label class="form-label">Role</label><input type="text" class="form-control bg-light" value="{{ $user->roleLabel() }}" readonly></div>
                    </div>
                    <div class="text-muted small mb-3">Username dan role hanya dapat diubah oleh admin desa.</div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">Ganti Kata Sandi</div>
            <div class="card-body">
                <form id="formPassword" action="{{ route('profil.password') }}" data-reset novalidate autocomplete="off">
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Kata sandi lama</label>
                        <input type="password" name="password_lama" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kata sandi baru</label>
                            <input type="password" name="password" class="form-control" autocomplete="new-password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ulangi kata sandi baru</label>
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-key me-1"></i>Ganti Kata Sandi</button>
                </form>
            </div>
        </div>
    </div>

    @if ($user->isWarga())
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header fw-semibold">Data Kependudukan</div>
                <div class="card-body">
                    @if ($penduduk)
                        <dl class="row mb-0">
                            <dt class="col-sm-4">NIK</dt><dd class="col-sm-8">{{ $penduduk->nik }}</dd>
                            <dt class="col-sm-4">Nama</dt><dd class="col-sm-8">{{ $penduduk->nama }}</dd>
                            <dt class="col-sm-4">No. KK</dt><dd class="col-sm-8">{{ $penduduk->no_kk }}</dd>
                            <dt class="col-sm-4">TTL</dt><dd class="col-sm-8">{{ $penduduk->tempat_lahir }}, {{ $penduduk->tanggal_lahir->translatedFormat('d F Y') }}</dd>
                            <dt class="col-sm-4">Pekerjaan</dt><dd class="col-sm-8">{{ $penduduk->pekerjaan ?: '-' }}</dd>
                            <dt class="col-sm-4">Alamat</dt><dd class="col-sm-8">{{ $penduduk->alamat ?: '-' }}</dd>
                            <dt class="col-sm-4">Dusun/RT</dt><dd class="col-sm-8">{{ $penduduk->wilayah?->label ?? '-' }}</dd>
                        </dl>
                        <div class="text-muted small mt-3">Jika ada data yang keliru, hubungi admin desa untuk perbaikan.</div>
                    @else
                        <div class="alert alert-warning mb-0">Akun Anda belum terhubung dengan data penduduk. Hubungi admin desa.</div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            AppAjaxForm('#formProfil');
            AppAjaxForm('#formPassword');
        });
    </script>
@endpush

@extends('layouts.app')
@php($edit = $b->exists)
@section('title', $edit ? 'Ubah Bidang Tanah' : 'Tambah Bidang Tanah')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.bidang-tanah.index') }}">Bidang Tanah</a></li>
    <li class="breadcrumb-item active">{{ $edit ? 'Ubah' : 'Tambah' }}</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
<form id="formBidang" novalidate autocomplete="off"
      action="{{ $edit ? route('admin.bidang-tanah.update', $b) : route('admin.bidang-tanah.store') }}">
    @if ($edit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header fw-semibold">Data Bidang Tanah</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">No. persil</label>
                        <input type="text" name="no_persil" class="form-control" value="{{ $b->no_persil }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pemilik (NIK / nama)</label>
                        <select name="nik_pemilik" class="form-select">
                            <option></option>
                            @if ($edit && $b->pemilik)
                                <option value="{{ $b->nik_pemilik }}" selected>{{ $b->nik_pemilik }} — {{ $b->pemilik->nama }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Luas tercatat (m²)</label>
                            <input type="number" step="0.01" min="0" name="luas_m2" class="form-control" value="{{ $b->luas_m2 }}">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Luas dari peta (m²)</label>
                            <input type="text" id="luasGeometri" class="form-control bg-light" readonly value="{{ $b->luas_geometri_m2 }}">
                            <small class="text-muted">Otomatis dari polygon</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Status hak</label>
                            <select name="status_hak" class="form-select">
                                @foreach ($statusHak as $v)<option value="{{ $v }}" @selected($b->status_hak === $v)>{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Penggunaan lahan</label>
                            <select name="penggunaan_lahan" class="form-select">
                                @foreach ($penggunaan as $v)<option value="{{ $v }}" @selected($b->penggunaan_lahan === $v)>{{ ucfirst($v) }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. sertifikat <small class="text-muted">(wajib untuk SHM/HGB)</small></label>
                        <input type="text" name="no_sertifikat" class="form-control" value="{{ $b->no_sertifikat }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dusun / RT</label>
                        <select name="wilayah_id" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($wilayah as $id => $label)<option value="{{ $id }}" @selected($b->wilayah_id == $id)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Batas utara</label><input type="text" name="batas_utara" class="form-control" value="{{ $b->batas_utara }}"></div>
                        <div class="col-6 mb-3"><label class="form-label">Batas selatan</label><input type="text" name="batas_selatan" class="form-control" value="{{ $b->batas_selatan }}"></div>
                        <div class="col-6 mb-3"><label class="form-label">Batas timur</label><input type="text" name="batas_timur" class="form-control" value="{{ $b->batas_timur }}"></div>
                        <div class="col-6 mb-3"><label class="form-label">Batas barat</label><input type="text" name="batas_barat" class="form-control" value="{{ $b->batas_barat }}"></div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" rows="2" class="form-control">{{ $b->keterangan }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-semibold">Polygon di Peta</span>
                    <label class="btn btn-sm btn-outline-secondary mb-0">
                        <i class="bi bi-upload me-1"></i>Impor GeoJSON / KML
                        <input type="file" id="fileImpor" accept=".geojson,.json,.kml" hidden>
                    </label>
                </div>
                <div class="card-body">
                    <div id="peringatan" class="alert alert-warning py-2 small d-none"></div>
                    <div id="peta" class="peta peta-form"></div>
                    <input type="hidden" name="geojson" id="geojson" value="{{ $b->geojson ? json_encode($b->geojson) : '' }}">
                    <div class="mt-2 small text-muted">
                        Gunakan ikon poligon di kiri atas peta untuk menggambar. Luas dihitung otomatis: <strong id="luasTeks">—</strong>
                    </div>
                </div>
            </div>
            <div class="text-end mt-3">
                <a href="{{ route('admin.bidang-tanah.index') }}" class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    @vite(['resources/js/tables.js', 'resources/js/peta.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            AppSelect2('select[name=nik_pemilik]', {
                url: @json(route('admin.cari.penduduk')),
                placeholder: 'Cari NIK atau nama penduduk',
            });

            Peta.formPolygon({
                el: 'peta',
                pusat: @json(config('desa.peta.pusat')),
                zoom: @json(config('desa.peta.zoom')),
                input: '#geojson', luasInput: '#luasGeometri', luasTeks: '#luasTeks', peringatan: '#peringatan',
                fileInput: '#fileImpor',
                geojsonUrl: @json(route('api.bidang-tanah.geojson')),
                excludeId: @json($b->id),
                initial: @json($b->geojson),
            });

            AppAjaxForm('#formBidang');
        });
    </script>
@endpush

@extends('layouts.app')
@section('title', 'Peta Desa')
@section('breadcrumb')
    <li class="breadcrumb-item active">Peta Desa</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-12 col-lg-3">
                <label class="form-label small mb-1">{{ $publik ? 'Cari No. persil' : 'Cari No. persil / NIK pemilik' }}</label>
                <select id="cariPersil" class="form-select form-select-sm"><option></option></select>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label small mb-1">Dusun / RT</label>
                <select id="fWilayah" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($wilayah as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small mb-1">Status hak</label>
                <select id="fStatus" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($statusHak as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small mb-1">Penggunaan</label>
                <select id="fPenggunaan" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($penggunaan as $v)<option value="{{ $v }}">{{ ucfirst($v) }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label small mb-1">Warnai berdasarkan</label>
                <select id="fMode" class="form-select form-select-sm">
                    <option value="status">Status hak</option>
                    <option value="penggunaan">Penggunaan lahan</option>
                </select>
            </div>
        </div>
        <div id="peta" class="peta"></div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/tables.js', 'resources/js/peta.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const peta = Peta.petaDesa({
                el: 'peta',
                pusat: @json(config('desa.peta.pusat')),
                zoom: @json(config('desa.peta.zoom')),
                urls: @json($urls),
                tandaiMilik: @json($publik),
                filter: () => ({
                    wilayah_id: $('#fWilayah').val(),
                    status_hak: $('#fStatus').val(),
                    penggunaan_lahan: $('#fPenggunaan').val(),
                }),
                mode: () => $('#fMode').val(),
            });

            $('#fWilayah, #fStatus, #fPenggunaan').on('change', () => peta.muat());
            $('#fMode').on('change', () => peta.ubahMode());

            AppSelect2('#cariPersil', {
                url: @json($urls['cari']),
                placeholder: @json($publik ? 'Ketik no. persil' : 'Ketik no. persil atau NIK'),
                minimumInputLength: 1,
            }).on('select2:select', (e) => peta.fokus(e.params.data.id, e.params.data.bbox));
        });
    </script>
@endpush

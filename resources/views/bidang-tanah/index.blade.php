@extends('layouts.app')
@section('title', 'Bidang Tanah')
@section('breadcrumb')
    <li class="breadcrumb-item active">Bidang Tanah</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1">Dusun / RT</label>
                <select id="filterWilayah" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($wilayah as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Status hak</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($statusHak as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Penggunaan</label>
                <select id="filterPenggunaan" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($penggunaan as $v)<option value="{{ $v }}">{{ ucfirst($v) }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 text-md-end">
                <a href="{{ route('admin.bidang-tanah.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Bidang Tanah</a>
            </div>
        </div>
        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th style="width:50px">No</th><th>No. Persil</th><th>Pemilik</th><th>Luas</th>
                        <th>Status Hak</th><th>Penggunaan</th><th>Dusun/RT</th><th style="width:130px">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/tables.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const base = @json(route('admin.bidang-tanah.index'));

            tableSkeleton('#tabel', 8);
            const table = AppTable('#tabel', {
                ajax: {
                    url: @json(route('admin.bidang-tanah.data')),
                    data: d => {
                        d.wilayah_id = $('#filterWilayah').val();
                        d.status_hak = $('#filterStatus').val();
                        d.penggunaan_lahan = $('#filterPenggunaan').val();
                    },
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'no_persil' },
                    { data: 'pemilik', orderable: false, searchable: false },
                    { data: 'luas_m2' },
                    { data: 'status_hak' },
                    { data: 'penggunaan_lahan' },
                    { data: 'dusun', orderable: false, searchable: false },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
                order: [[1, 'asc']],
            });
            $('#filterWilayah, #filterStatus, #filterPenggunaan').on('change', () => table.ajax.reload());
            $('#tabel').on('click', '.btn-hapus', function () { confirmDelete(`${base}/${$(this).data('id')}`, table); });
        });
    </script>
@endpush

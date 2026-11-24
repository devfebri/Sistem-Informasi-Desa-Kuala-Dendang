@extends('layouts.app')
@section('title', 'Bidang Tanah Saya')
@section('breadcrumb')
    <li class="breadcrumb-item active">Bidang Tanah Saya</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th style="width:50px">No</th><th>No. Persil</th><th>Luas</th><th>Status Hak</th>
                        <th>No. Sertifikat</th><th>Penggunaan</th><th>Dusun/RT</th>
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
            tableSkeleton('#tabel', 7);
            AppTable('#tabel', {
                ajax: @json(route('warga.bidang.data')),
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'no_persil' },
                    { data: 'luas_m2' },
                    { data: 'status_hak' },
                    { data: 'no_sertifikat', orderable: false },
                    { data: 'penggunaan_lahan' },
                    { data: 'dusun', orderable: false, searchable: false },
                ],
                order: [[1, 'asc']],
            });
        });
    </script>
@endpush

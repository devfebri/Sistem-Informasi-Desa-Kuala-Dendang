@extends('layouts.app')
@section('title', 'Log Aktivitas')
@section('breadcrumb')
    <li class="breadcrumb-item active">Log Aktivitas</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Aksi</label>
                <select id="fAksi" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($aksi as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Objek</label>
                <select id="fModel" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($model as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 small text-muted align-self-end">Pencarian: awalan no. persil / nama / username / no. registrasi.</div>
        </div>
        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover table-sm w-100">
                <thead>
                    <tr>
                        <th style="width:50px">No</th><th>Waktu</th><th>Pengguna</th><th>Aksi</th>
                        <th>Objek</th><th>Perubahan</th><th>IP</th>
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
            const table = AppTable('#tabel', {
                ajax: { url: @json(route('admin.log.data')), data: d => { d.aksi = $('#fAksi').val(); d.model = $('#fModel').val(); } },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at' },
                    { data: 'pengguna', orderable: false, searchable: false },
                    { data: 'aksi', orderable: false },
                    { data: 'objek', orderable: false, searchable: false },
                    { data: 'rincian', orderable: false, searchable: false },
                    { data: 'ip', orderable: false },
                ],
                order: [[1, 'desc']],
            });
            $('#fAksi, #fModel').on('change', () => table.ajax.reload());
        });
    </script>
@endpush

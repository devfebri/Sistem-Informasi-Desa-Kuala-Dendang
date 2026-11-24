@extends('layouts.app')
@section('title', $peran === 'kades' ? 'Validasi Surat' : 'Pengajuan Surat')
@section('breadcrumb')
    <li class="breadcrumb-item active">Pengajuan Surat</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1">Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($status as $k => $v)
                        @continue($peran === 'kades' && $k === 'diajukan')
                        <option value="{{ $k }}" @selected($k === $statusAwal)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-8 text-md-end small text-muted">
                @if ($peran === 'kades')
                    Antrean awal menampilkan pengajuan yang sudah diverifikasi admin dan menunggu persetujuan Anda.
                @else
                    Antrean awal menampilkan pengajuan baru yang menunggu verifikasi.
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th style="width:50px">No</th><th>No. Registrasi</th><th>Pemohon</th><th>Jenis Surat</th>
                        <th>Persil</th><th>Diajukan</th><th>Status</th><th style="width:70px">Aksi</th>
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
            tableSkeleton('#tabel', 8);
            const table = AppTable('#tabel', {
                ajax: { url: @json(route("$peran.pengajuan.data")), data: d => { d.status = $('#filterStatus').val(); } },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'no_registrasi' },
                    { data: 'pemohon', orderable: false, searchable: false },
                    { data: 'jenis', orderable: false, searchable: false },
                    { data: 'persil', orderable: false, searchable: false },
                    { data: 'created_at' },
                    { data: 'status', orderable: false },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
                order: [[5, 'asc']], // yang paling lama menunggu di atas
            });
            $('#filterStatus').on('change', () => table.ajax.reload());
        });
    </script>
@endpush

@extends('layouts.app')
@section('title', 'Wilayah (Dusun/RT)')
@section('breadcrumb')
    <li class="breadcrumb-item active">Wilayah</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
<div class="card">
    <div class="card-body">
        <div class="text-end mb-3">
            <button type="button" class="btn btn-primary" id="btnTambah"><i class="bi bi-plus-lg me-1"></i>Tambah Wilayah</button>
        </div>
        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th style="width:50px">No</th><th>Dusun</th><th>RT</th><th>RW</th>
                        <th>Jumlah KK</th><th>Jumlah Penduduk</th><th style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalForm" tabindex="-1" data-title-create="Tambah Wilayah" data-title-edit="Ubah Wilayah">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <form class="modal-content" novalidate>
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama dusun</label>
                    <input type="text" name="nama_dusun" class="form-control" placeholder="mis. Dusun Mekar Jaya">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">RT</label>
                        <input type="text" name="rt" class="form-control" maxlength="3" inputmode="numeric">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">RW</label>
                        <input type="text" name="rw" class="form-control" maxlength="3" inputmode="numeric">
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label">Batas wilayah <small class="text-muted">(opsional, GeoJSON Polygon)</small></label>
                    <textarea name="geojson" class="form-control font-monospace small" rows="4" placeholder='{"type":"Polygon","coordinates":[[[103.0,-1.0],...]]}'></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/tables.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const base = @json(route('admin.wilayah.index'));

            tableSkeleton('#tabel', 7);
            const table = AppTable('#tabel', {
                ajax: @json(route('admin.wilayah.data')),
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nama_dusun' },
                    { data: 'rt' },
                    { data: 'rw' },
                    { data: 'kartu_keluarga_count', orderable: false, searchable: false },
                    { data: 'penduduk_count', orderable: false, searchable: false },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
                order: [[1, 'asc']],
            });

            const form = AppModalForm({
                modal: '#modalForm', table, storeUrl: base,
                updateUrl: id => `${base}/${id}`, editUrl: id => `${base}/${id}/edit`,
            });

            $('#btnTambah').on('click', () => form.create());
            $('#tabel').on('click', '.btn-edit', function () { form.edit($(this).data('id')); });
            $('#tabel').on('click', '.btn-hapus', function () { confirmDelete(`${base}/${$(this).data('id')}`, table); });
        });
    </script>
@endpush

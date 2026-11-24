@extends('layouts.app')
@section('title', 'Kartu Keluarga')
@section('breadcrumb')
    <li class="breadcrumb-item active">Kartu Keluarga</li>
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
                    @foreach ($wilayah as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-8 text-md-end">
                <button type="button" class="btn btn-primary" id="btnTambah"><i class="bi bi-plus-lg me-1"></i>Tambah KK</button>
            </div>
        </div>
        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th style="width:50px">No</th><th>No. KK</th><th>Kepala Keluarga</th><th>Alamat</th>
                        <th>Dusun/RT</th><th>Anggota</th><th style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalForm" tabindex="-1" data-title-create="Tambah Kartu Keluarga" data-title-edit="Ubah Kartu Keluarga">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <form class="modal-content" novalidate>
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">No. KK (16 digit)</label>
                    <input type="text" name="no_kk" class="form-control" maxlength="16" inputmode="numeric">
                </div>
                <div class="mb-3">
                    <label class="form-label">Kepala keluarga <small class="text-muted">(opsional, pilih setelah penduduk dibuat)</small></label>
                    <select name="nik_kepala_keluarga" class="form-select"><option></option></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <input type="text" name="alamat" class="form-control">
                </div>
                <div class="mb-1">
                    <label class="form-label">Dusun / RT</label>
                    <select name="wilayah_id" class="form-select">
                        <option value="">— pilih —</option>
                        @foreach ($wilayah as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
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
            const base = @json(route('admin.kartu-keluarga.index'));
            const $kepala = AppSelect2('#modalForm [name=nik_kepala_keluarga]', {
                url: @json(route('admin.cari.penduduk')),
                placeholder: 'Cari NIK / nama penduduk',
                dropdownParent: '#modalForm',
            });

            tableSkeleton('#tabel', 7);
            const table = AppTable('#tabel', {
                ajax: { url: @json(route('admin.kartu-keluarga.data')), data: d => { d.wilayah_id = $('#filterWilayah').val(); } },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'no_kk' },
                    { data: 'kepala', orderable: false, searchable: false },
                    { data: 'alamat', orderable: false },
                    { data: 'dusun', orderable: false, searchable: false },
                    { data: 'anggota_count', orderable: false, searchable: false },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
                order: [[1, 'asc']],
            });
            $('#filterWilayah').on('change', () => table.ajax.reload());

            const form = AppModalForm({
                modal: '#modalForm', table, storeUrl: base,
                updateUrl: id => `${base}/${id}`, editUrl: id => `${base}/${id}/edit`,
                reset: () => $kepala.val(null).trigger('change'),
                fill: d => {
                    $kepala.empty().append(new Option('', '', false, false));
                    if (d.nik_kepala_keluarga) $kepala.append(new Option(d.kepala_text || d.nik_kepala_keluarga, d.nik_kepala_keluarga, true, true));
                    $kepala.trigger('change');
                },
            });

            $('#btnTambah').on('click', () => form.create());
            $('#tabel').on('click', '.btn-edit', function () { form.edit($(this).data('id')); });
            $('#tabel').on('click', '.btn-hapus', function () { confirmDelete(`${base}/${$(this).data('id')}`, table); });
        });
    </script>
@endpush

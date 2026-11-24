@extends('layouts.app')
@section('title', 'Data Penduduk')
@section('breadcrumb')
    <li class="breadcrumb-item active">Data Penduduk</li>
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
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($status as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Jenis kelamin</label>
                <select id="filterJk" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($jenisKelamin as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 text-md-end">
                <button type="button" class="btn btn-primary" id="btnTambah"><i class="bi bi-plus-lg me-1"></i>Tambah Penduduk</button>
            </div>
        </div>
        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th style="width:50px">No</th><th>NIK</th><th>Nama</th><th>JK</th><th>Tgl. Lahir</th>
                        <th>No. KK</th><th>Dusun/RT</th><th>Status</th><th style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalForm" tabindex="-1" data-title-create="Tambah Penduduk" data-title-edit="Ubah Data Penduduk">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <form class="modal-content" novalidate>
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIK (16 digit)</label>
                        <input type="text" name="nik" class="form-control" maxlength="16" inputmode="numeric">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">No. KK</label>
                        <select name="no_kk" class="form-select"><option></option></select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama lengkap</label>
                    <input type="text" name="nama" class="form-control">
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Jenis kelamin</label>
                        <select name="jenis_kelamin" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($jenisKelamin as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tempat lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tanggal lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Agama</label>
                        <select name="agama" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($agama as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status perkawinan</label>
                        <select name="status_perkawinan" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($statusPerkawinan as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Hubungan keluarga</label>
                        <select name="hubungan_keluarga" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($hubungan as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pekerjaan</label>
                        <input type="text" name="pekerjaan" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Dusun / RT</label>
                        <select name="wilayah_id" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($wilayah as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8 mb-1">
                        <label class="form-label">Alamat</label>
                        <input type="text" name="alamat" class="form-control">
                    </div>
                    <div class="col-md-4 mb-1">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            @foreach ($status as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
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
            const base = @json(route('admin.penduduk.index'));
            const $kk = AppSelect2('#modalForm [name=no_kk]', {
                url: @json(route('admin.cari.kk')),
                placeholder: 'Cari No. KK',
                dropdownParent: '#modalForm',
            });

            tableSkeleton('#tabel', 9);
            const table = AppTable('#tabel', {
                ajax: {
                    url: @json(route('admin.penduduk.data')),
                    data: d => {
                        d.wilayah_id = $('#filterWilayah').val();
                        d.status = $('#filterStatus').val();
                        d.jenis_kelamin = $('#filterJk').val();
                    },
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nik' },
                    { data: 'nama' },
                    { data: 'jenis_kelamin', orderable: false },
                    { data: 'tanggal_lahir' },
                    { data: 'no_kk' },
                    { data: 'dusun', orderable: false, searchable: false },
                    { data: 'status', orderable: false },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
                order: [[2, 'asc']],
            });
            $('#filterWilayah, #filterStatus, #filterJk').on('change', () => table.ajax.reload());

            const form = AppModalForm({
                modal: '#modalForm', table, storeUrl: base,
                updateUrl: id => `${base}/${id}`, editUrl: id => `${base}/${id}/edit`,
                reset: ($f) => {
                    $f.find('[name=nik]').prop('readonly', false);
                    $kk.val(null).trigger('change');
                    $f.find('[name=status]').val('hidup');
                },
                fill: (d, $f) => {
                    $f.find('[name=nik]').prop('readonly', true); // NIK tidak dapat diubah
                    $kk.empty().append(new Option(d.no_kk, d.no_kk, true, true)).trigger('change');
                },
            });

            $('#btnTambah').on('click', () => form.create());
            $('#tabel').on('click', '.btn-edit', function () { form.edit($(this).data('id')); });
            $('#tabel').on('click', '.btn-hapus', function () { confirmDelete(`${base}/${$(this).data('id')}`, table); });
        });
    </script>
@endpush

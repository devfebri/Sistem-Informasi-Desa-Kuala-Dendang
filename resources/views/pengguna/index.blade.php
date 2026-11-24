@extends('layouts.app')
@section('title', 'Kelola Akun Pengguna')
@section('breadcrumb')
    <li class="breadcrumb-item active">Akun Pengguna</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Role</label>
                <select id="filterRole" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($roles as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <div class="col-12 col-md-6 text-md-end">
                <button type="button" class="btn btn-primary" id="btnTambah">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Akun
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>NIK</th>
                        <th>Status</th>
                        <th style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal tambah/ubah --}}
<div class="modal fade" id="modalForm" tabindex="-1" data-title-create="Tambah Akun Pengguna" data-title-edit="Ubah Akun Pengguna">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <form class="modal-content" autocomplete="off" novalidate>
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama lengkap</label>
                    <input type="text" name="name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kata sandi <small class="text-muted" id="hintPassword"></small></label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ulangi kata sandi</label>
                        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            @foreach ($roles as $k => $v)
                                <option value="{{ $k }}" @selected($k === 'warga')>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="mb-1" id="wrapNik">
                    <label class="form-label">NIK (16 digit, khusus warga)</label>
                    <input type="text" name="nik" class="form-control" maxlength="16" inputmode="numeric">
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
            const base = @json(route('admin.pengguna.index'));

            tableSkeleton('#tabel', 7);
            const table = AppTable('#tabel', {
                ajax: {
                    url: @json(route('admin.pengguna.data')),
                    data: d => { d.role = $('#filterRole').val(); d.status = $('#filterStatus').val(); },
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name' },
                    { data: 'username' },
                    { data: 'role' },
                    { data: 'nik' },
                    { data: 'is_active', orderable: false },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
                order: [[1, 'asc']],
            });

            $('#filterRole, #filterStatus').on('change', () => table.ajax.reload());

            const form = AppModalForm({
                modal: '#modalForm',
                table,
                storeUrl: base,
                updateUrl: id => `${base}/${id}`,
                editUrl: id => `${base}/${id}/edit`,
                reset: () => { $('#hintPassword').text(''); toggleNik(); },
                fill: () => { $('#hintPassword').text('(kosongkan jika tidak diubah)'); toggleNik(); },
            });

            const toggleNik = () => $('#wrapNik').toggle($('#modalForm [name=role]').val() === 'warga');
            $('#modalForm [name=role]').on('change', toggleNik);

            $('#btnTambah').on('click', () => form.create());
            $('#tabel').on('click', '.btn-edit', function () { form.edit($(this).data('id')); });
            $('#tabel').on('click', '.btn-hapus', function () { confirmDelete(`${base}/${$(this).data('id')}`, table); });
        });
    </script>
@endpush

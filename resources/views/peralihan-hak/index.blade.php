@extends('layouts.app')
@section('title', 'Peralihan Hak')
@section('breadcrumb')
    <li class="breadcrumb-item active">Peralihan Hak</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1">Jenis peralihan</label>
                <select id="filterJenis" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($jenis as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-9 text-md-end">
                <button type="button" class="btn btn-primary" id="btnTambah"><i class="bi bi-plus-lg me-1"></i>Catat Peralihan Hak</button>
            </div>
        </div>
        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th style="width:50px">No</th><th>Tanggal</th><th>No. Persil</th><th>Pemilik Lama</th>
                        <th>Pemilik Baru</th><th>Jenis</th><th>No. Akta</th><th>Berkas</th><th>Dicatat Oleh</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>Riwayat peralihan hak bersifat permanen (tidak dapat diubah atau dihapus) untuk menjaga jejak audit.</div>
    </div>
</div>

<div class="modal fade" id="modalForm" tabindex="-1" data-title-create="Catat Peralihan Hak">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <form class="modal-content" novalidate enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Bidang tanah</label>
                    <select name="bidang_tanah_id" class="form-select"><option></option></select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pemilik lama <small class="text-muted">(otomatis)</small></label>
                        <input type="text" id="pemilikLama" class="form-control bg-light" readonly placeholder="Pilih bidang tanah dahulu">
                        <input type="hidden" name="nik_pemilik_lama">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pemilik baru (NIK / nama)</label>
                        <select name="nik_pemilik_baru" class="form-select"><option></option></select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Jenis peralihan</label>
                        <select name="jenis" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($jenis as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" max="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">No. akta <small class="text-muted">(opsional)</small></label>
                        <input type="text" name="no_akta" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Dokumen pendukung <small class="text-muted">(PDF/JPG/PNG, maks. 5 MB)</small></label>
                    <input type="file" name="dokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="mb-1">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" rows="2" class="form-control"></textarea>
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
            const $bidang = AppSelect2('#modalForm [name=bidang_tanah_id]', {
                url: @json(route('admin.cari.bidang-tanah')),
                placeholder: 'Cari no. persil atau NIK pemilik',
                dropdownParent: '#modalForm',
                minimumInputLength: 1,
            });
            const $baru = AppSelect2('#modalForm [name=nik_pemilik_baru]', {
                url: @json(route('admin.cari.penduduk')),
                placeholder: 'Cari NIK atau nama pemilik baru',
                dropdownParent: '#modalForm',
            });

            const setLama = (nik, nama) => {
                $('#modalForm [name=nik_pemilik_lama]').val(nik || '');
                $('#pemilikLama').val(nik ? `${nik} — ${nama ?? ''}` : '');
            };
            $bidang.on('select2:select', (e) => setLama(e.params.data.pemilik_nik, e.params.data.pemilik_nama));
            $bidang.on('select2:clear', () => setLama('', ''));

            tableSkeleton('#tabel', 9);
            const table = AppTable('#tabel', {
                ajax: { url: @json(route('admin.peralihan-hak.data')), data: d => { d.jenis = $('#filterJenis').val(); } },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'tanggal' },
                    { data: 'persil', orderable: false, searchable: false },
                    { data: 'lama', orderable: false, searchable: false },
                    { data: 'baru', orderable: false, searchable: false },
                    { data: 'jenis', orderable: false },
                    { data: 'no_akta', orderable: false },
                    { data: 'berkas', orderable: false, searchable: false },
                    { data: 'pencatat', orderable: false, searchable: false },
                ],
                order: [[1, 'desc']],
            });
            $('#filterJenis').on('change', () => table.ajax.reload());

            const form = AppModalForm({
                modal: '#modalForm', table, storeUrl: @json(route('admin.peralihan-hak.store')),
                reset: ($f) => {
                    $bidang.val(null).trigger('change');
                    $baru.val(null).trigger('change');
                    setLama('', '');
                    $f.find('[name=tanggal]').val(@json(now()->toDateString()));
                },
            });
            $('#btnTambah').on('click', () => form.create());

            // Dibuka dari halaman detail bidang tanah: ?bidang_tanah_id=...
            const awal = @json($bidangAwal);
            if (awal) {
                form.create();
                $bidang.append(new Option(awal.text, awal.id, true, true)).trigger('change');
                setLama(awal.pemilik_nik, awal.pemilik_nama);
            }
        });
    </script>
@endpush

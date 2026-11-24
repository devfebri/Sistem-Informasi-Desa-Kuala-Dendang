@extends('layouts.app')
@section('title', 'Ajukan & Lacak Surat')
@section('breadcrumb')
    <li class="breadcrumb-item active">Surat Pertanahan</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
@if (! $pemohon)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>Akun Anda belum terhubung dengan data kependudukan. Silakan hubungi admin desa untuk menghubungkan NIK Anda.
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1">Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach (\App\Models\PengajuanSurat::STATUS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-8 text-md-end">
                <button type="button" class="btn btn-primary" id="btnTambah" @disabled(! $pemohon || $bidang->isEmpty())>
                    <i class="bi bi-plus-lg me-1"></i>Ajukan Surat
                </button>
                @if ($pemohon && $bidang->isEmpty())
                    <div class="small text-muted mt-1">Anda belum memiliki bidang tanah yang tercatat.</div>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th style="width:50px">No</th><th>No. Registrasi</th><th>Jenis Surat</th><th>Persil</th>
                        <th>Diajukan</th><th>Status</th><th style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalForm" tabindex="-1" data-title-create="Ajukan Surat Pertanahan">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <form class="modal-content" novalidate enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis surat</label>
                        <select name="jenis_surat_id" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($jenis as $j)<option value="{{ $j->id }}">{{ $j->nama }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bidang tanah</label>
                        <select name="bidang_tanah_id" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($bidang as $b)
                                <option value="{{ $b->id }}">{{ $b->no_persil }} ({{ number_format($b->luas_m2, 0, ',', '.') }} m², {{ $b->status_hak }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keperluan</label>
                    <textarea name="keperluan" rows="2" class="form-control" placeholder="mis. pengurusan sertifikat, jual beli, agunan bank"></textarea>
                </div>
                <div id="wrapDokumen">
                    <div class="text-muted small">Pilih jenis surat untuk melihat dokumen persyaratan yang harus diunggah.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/tables.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const persyaratan = @json($jenis->mapWithKeys(fn ($j) => [$j->id => $j->persyaratan ?? []]));

            tableSkeleton('#tabel', 7);
            const table = AppTable('#tabel', {
                ajax: { url: @json(route('warga.surat.data')), data: d => { d.status = $('#filterStatus').val(); } },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'no_registrasi' },
                    { data: 'jenis', orderable: false, searchable: false },
                    { data: 'persil', orderable: false, searchable: false },
                    { data: 'created_at' },
                    { data: 'status', orderable: false },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
                order: [[4, 'desc']],
            });
            $('#filterStatus').on('change', () => table.ajax.reload());

            const esc = (s) => $('<div>').text(s).html();
            const bangunInputDokumen = (id) => {
                const daftar = persyaratan[id] || [];
                $('#wrapDokumen').html(daftar.length
                    ? '<div class="fw-semibold mb-2">Dokumen persyaratan <small class="text-muted fw-normal">(PDF/JPG/PNG, maks. 5 MB per berkas)</small></div>'
                        + daftar.map((nama, i) => `<div class="mb-2"><label class="form-label small mb-1">${esc(nama)}</label>`
                            + `<input type="file" name="dokumen[${i}]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png"></div>`).join('')
                    : '<div class="text-muted small">Pilih jenis surat untuk melihat dokumen persyaratan yang harus diunggah.</div>');
            };
            $('#modalForm [name=jenis_surat_id]').on('change', function () { bangunInputDokumen($(this).val()); });

            const form = AppModalForm({
                modal: '#modalForm', table, storeUrl: @json(route('warga.surat.store')),
                reset: () => bangunInputDokumen(null),
            });
            $('#btnTambah').on('click', () => form.create());
        });
    </script>
@endpush

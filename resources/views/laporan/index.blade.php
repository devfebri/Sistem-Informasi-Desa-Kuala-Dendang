@extends('layouts.app')
@section('title', $l->judul())
@section('breadcrumb')
    <li class="breadcrumb-item">Laporan</li>
    <li class="breadcrumb-item active">{{ $l->judul() }}</li>
@endsection

@push('styles')
    @vite('resources/sass/tables.scss')
@endpush

@section('content')
<ul class="nav nav-pills mb-3 flex-wrap">
    @foreach ($semua as $s)
        <li class="nav-item">
            <a class="nav-link {{ $s === $slug ? 'active' : '' }}" href="{{ route('laporan.index', $s) }}">
                {{ \App\Reports\Laporan::dari($s)->judul() }}
            </a>
        </li>
    @endforeach
</ul>

<div class="card">
    <div class="card-body">
        <form id="formFilter" class="row g-2 mb-3 align-items-end" novalidate>
            @foreach ($l->filter() as $f)
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">{{ $f['label'] }}</label>
                    @if ($f['type'] === 'date')
                        <input type="date" name="{{ $f['name'] }}" class="form-control form-control-sm" value="{{ $nilai[$f['name']] ?? '' }}">
                    @else
                        <select name="{{ $f['name'] }}" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($f['options'] as $k => $v)
                                <option value="{{ $k }}" @selected((string) ($nilai[$f['name']] ?? '') === (string) $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            @endforeach
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a id="btnPdf" target="_blank" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
                <a id="btnExcel" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
            </div>
        </form>

        <div class="table-responsive">
            <table id="tabel" class="table table-striped table-hover table-sm w-100">
                <thead><tr>@foreach ($l->kolom() as $k)<th>{{ $k }}</th>@endforeach</tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="small text-muted mt-2">PDF dibatasi 2.000 baris pertama; Excel memuat seluruh data sesuai filter.</div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/tables.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const urls = {
                data: @json(route('laporan.data', $slug)),
                pdf: @json(route('laporan.pdf', $slug)),
                excel: @json(route('laporan.excel', $slug)),
            };
            const param = () => $('#formFilter').serialize();
            const segarkanTautan = () => {
                $('#btnPdf').attr('href', urls.pdf + '?' + param());
                $('#btnExcel').attr('href', urls.excel + '?' + param());
            };
            segarkanTautan();

            tableSkeleton('#tabel', {{ count($l->kolom()) }});
            const table = AppTable('#tabel', {
                ajax: { url: urls.data, data: d => { $('#formFilter').serializeArray().forEach(f => d[f.name] = f.value); } },
                columns: @json(array_map(fn ($i) => ['data' => $i, 'orderable' => false], array_keys($l->kolom()))),
                searching: false,
                ordering: false,
                layout: { topStart: 'pageLength', topEnd: null, bottomStart: 'info', bottomEnd: 'paging' },
            });

            $('#formFilter').on('submit', function (e) {
                e.preventDefault();
                segarkanTautan();
                table.ajax.reload();
            });
        });
    </script>
@endpush

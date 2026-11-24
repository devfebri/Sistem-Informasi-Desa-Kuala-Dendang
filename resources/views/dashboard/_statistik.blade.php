@php
    $n = fn ($v) => number_format($v, 0, ',', '.');
    $ha = $stat['persil']['luas_m2'] / 10000;
@endphp

{{-- Antrean kerja (tidak di-cache) --}}
@if ($peran === 'admin')
    <a href="{{ route('admin.pengajuan.index') }}" class="alert alert-{{ $antrean['menunggu_verifikasi'] ? 'warning' : 'light border' }} d-flex align-items-center justify-content-between text-decoration-none">
        <span><i class="bi bi-inbox me-2"></i><strong>{{ $antrean['menunggu_verifikasi'] }}</strong> pengajuan surat menunggu verifikasi Anda</span>
        <i class="bi bi-chevron-right"></i>
    </a>
@else
    <a href="{{ route('kades.pengajuan.index') }}" class="alert alert-{{ $antrean['menunggu_persetujuan'] ? 'warning' : 'light border' }} d-flex align-items-center justify-content-between text-decoration-none">
        <span><i class="bi bi-patch-check me-2"></i><strong>{{ $antrean['menunggu_persetujuan'] }}</strong> pengajuan surat menunggu persetujuan Anda</span>
        <i class="bi bi-chevron-right"></i>
    </a>
@endif

{{-- Angka utama --}}
<div class="row g-3 mb-3">
    @foreach ([
        ['Penduduk', $n($stat['penduduk']['total']), 'L '.$n($stat['penduduk']['laki']).' · P '.$n($stat['penduduk']['perempuan']), 'people-fill'],
        ['Kartu Keluarga', $n($stat['kk']), 'terdaftar', 'card-list'],
        ['Bidang Tanah', $n($stat['persil']['total']), 'persil tercatat', 'bounding-box'],
        ['Total Luas', number_format($ha, 2, ',', '.').' ha', $n($stat['persil']['luas_m2']).' m²', 'rulers'],
    ] as [$label, $nilai, $sub, $ikon])
        <div class="col-6 col-xl-3">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-{{ $ikon }} fs-2 text-primary"></i>
                <div>
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-3 fw-semibold lh-1">{{ $nilai }}</div>
                    <div class="text-muted small mt-1">{{ $sub }}</div>
                </div>
            </div></div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h6 class="mb-0">Persil menurut status hak</h6>
            <div class="text-muted small mb-2">Jumlah bidang tanah</div>
            <div class="grafik"><canvas id="grafikStatusHak" role="img" aria-label="Grafik batang jumlah persil menurut status hak"></canvas></div>
            <details class="mt-2"><summary class="small text-muted">Lihat sebagai tabel</summary>
                <table class="table table-sm small mb-0 mt-1">
                    <thead><tr><th>Status hak</th><th class="text-end">Persil</th><th class="text-end">Luas (m²)</th></tr></thead>
                    <tbody>@foreach ($stat['status_hak'] as $s)
                        <tr><td>{{ $s['label'] }}</td><td class="text-end">{{ $n($s['persil']) }}</td><td class="text-end">{{ $n($s['luas_m2']) }}</td></tr>
                    @endforeach</tbody>
                </table>
            </details>
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h6 class="mb-0">Penggunaan lahan per dusun</h6>
            <div class="text-muted small mb-2">Luas dalam m²</div>
            <div class="grafik"><canvas id="grafikPenggunaan" role="img" aria-label="Grafik batang bertumpuk luas penggunaan lahan per dusun"></canvas></div>
            <details class="mt-2"><summary class="small text-muted">Lihat sebagai tabel</summary>
                <div class="table-responsive"><table class="table table-sm small mb-0 mt-1">
                    <thead><tr><th>Dusun</th>@foreach ($stat['penggunaan_per_dusun']['seri'] as $s)<th class="text-end">{{ $s['label'] }}</th>@endforeach</tr></thead>
                    <tbody>@foreach ($stat['penggunaan_per_dusun']['dusun'] as $i => $d)
                        <tr><td>{{ $d }}</td>@foreach ($stat['penggunaan_per_dusun']['seri'] as $s)<td class="text-end">{{ $n($s['nilai'][$i]) }}</td>@endforeach</tr>
                    @endforeach</tbody>
                </table></div>
            </details>
        </div></div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100"><div class="card-body">
            <h6 class="mb-0">Pengajuan surat per bulan</h6>
            <div class="text-muted small mb-2">12 bulan terakhir</div>
            <div class="grafik"><canvas id="grafikSuratBulan" role="img" aria-label="Grafik batang jumlah pengajuan surat per bulan"></canvas></div>
            <details class="mt-2"><summary class="small text-muted">Lihat sebagai tabel</summary>
                <div class="table-responsive"><table class="table table-sm small mb-0 mt-1">
                    <thead><tr><th>Bulan</th>@foreach ($stat['surat_per_bulan']['label'] as $l)<th class="text-end">{{ $l }}</th>@endforeach</tr></thead>
                    <tbody><tr><td>Pengajuan</td>@foreach ($stat['surat_per_bulan']['nilai'] as $v)<td class="text-end">{{ $v }}</td>@endforeach</tr></tbody>
                </table></div>
            </details>
        </div></div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="mb-0">Pengajuan surat per status</h6>
            <div class="text-muted small mb-2">Seluruh waktu</div>
            <div class="grafik"><canvas id="grafikSuratStatus" role="img" aria-label="Grafik batang jumlah pengajuan surat per status"></canvas></div>
            <details class="mt-2"><summary class="small text-muted">Lihat sebagai tabel</summary>
                <table class="table table-sm small mb-0 mt-1">
                    <tbody>@foreach ($stat['surat_per_status'] as $s)
                        <tr><td>{{ $s['label'] }}</td><td class="text-end">{{ $s['nilai'] }}</td></tr>
                    @endforeach</tbody>
                </table>
            </details>
        </div></div>
    </div>
</div>

<div class="text-muted small mt-3">
    <i class="bi bi-clock-history me-1"></i>Statistik diperbarui {{ \Illuminate\Support\Carbon::parse($stat['dibuat'])->diffForHumans() }}
    (disimpan ±10 menit, otomatis diperbarui saat data berubah).
</div>

@push('scripts')
    @vite('resources/js/dashboard.js')
    <script>
        document.addEventListener('DOMContentLoaded', () => Dashboard.render(@json($stat)));
    </script>
@endpush

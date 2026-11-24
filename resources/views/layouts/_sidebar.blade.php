@php
    $menu = match (auth()->user()->role) {
        'admin' => [
            ['label' => 'Utama'],
            ['icon' => 'speedometer2', 'text' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard'],
            ['label' => 'Data Kependudukan'],
            ['icon' => 'people-fill', 'text' => 'Data Penduduk', 'route' => 'admin.penduduk.index', 'match' => 'admin.penduduk.*'],
            ['icon' => 'card-list', 'text' => 'Kartu Keluarga', 'route' => 'admin.kartu-keluarga.index', 'match' => 'admin.kartu-keluarga.*'],
            ['icon' => 'signpost-split', 'text' => 'Wilayah (Dusun/RT)', 'route' => 'admin.wilayah.index', 'match' => 'admin.wilayah.*'],
            ['label' => 'Pertanahan & GIS'],
            ['icon' => 'map', 'text' => 'Peta Desa', 'route' => 'admin.peta', 'match' => 'admin.peta'],
            ['icon' => 'bounding-box', 'text' => 'Bidang Tanah', 'route' => 'admin.bidang-tanah.index', 'match' => 'admin.bidang-tanah.*'],
            ['icon' => 'arrow-left-right', 'text' => 'Peralihan Hak', 'route' => 'admin.peralihan-hak.index', 'match' => 'admin.peralihan-hak.*'],
            ['label' => 'Layanan Surat'],
            ['icon' => 'envelope-paper', 'text' => 'Pengajuan Surat', 'route' => 'admin.pengajuan.index', 'match' => 'admin.pengajuan.*'],
            ['label' => 'Laporan'],
            ['icon' => 'journal-bookmark', 'text' => 'Buku Tanah Desa', 'route' => 'laporan.index', 'params' => 'buku-tanah', 'match' => 'laporan.*', 'path' => 'laporan/buku-tanah*'],
            ['icon' => 'arrow-left-right', 'text' => 'Rekap Peralihan Hak', 'route' => 'laporan.index', 'params' => 'rekap-peralihan', 'match' => 'laporan.*', 'path' => 'laporan/rekap-peralihan*'],
            ['icon' => 'envelope-paper', 'text' => 'Rekap Surat', 'route' => 'laporan.index', 'params' => 'rekap-surat', 'match' => 'laporan.*', 'path' => 'laporan/rekap-surat*'],
            ['label' => 'Pengaturan'],
            ['icon' => 'people', 'text' => 'Kelola Akun Pengguna', 'route' => 'admin.pengguna.index', 'match' => 'admin.pengguna.*'],
            ['icon' => 'clock-history', 'text' => 'Log Aktivitas', 'route' => 'admin.log.index', 'match' => 'admin.log.*'],
        ],
        'kades' => [
            ['label' => 'Utama'],
            ['icon' => 'speedometer2', 'text' => 'Dashboard', 'route' => 'kades.dashboard', 'match' => 'kades.dashboard'],
            ['icon' => 'map', 'text' => 'Peta Desa', 'route' => 'kades.peta', 'match' => 'kades.peta'],
            ['label' => 'Layanan Surat'],
            ['icon' => 'patch-check', 'text' => 'Validasi Surat', 'route' => 'kades.pengajuan.index', 'match' => 'kades.pengajuan.*'],
            ['label' => 'Laporan'],
            ['icon' => 'journal-bookmark', 'text' => 'Buku Tanah Desa', 'route' => 'laporan.index', 'params' => 'buku-tanah', 'match' => 'laporan.*', 'path' => 'laporan/buku-tanah*'],
            ['icon' => 'arrow-left-right', 'text' => 'Rekap Peralihan Hak', 'route' => 'laporan.index', 'params' => 'rekap-peralihan', 'match' => 'laporan.*', 'path' => 'laporan/rekap-peralihan*'],
            ['icon' => 'envelope-paper', 'text' => 'Rekap Surat', 'route' => 'laporan.index', 'params' => 'rekap-surat', 'match' => 'laporan.*', 'path' => 'laporan/rekap-surat*'],
        ],
        default => [
            ['label' => 'Utama'],
            ['icon' => 'house', 'text' => 'Beranda', 'route' => 'warga.beranda', 'match' => 'warga.beranda'],
            ['icon' => 'map', 'text' => 'Peta Desa', 'route' => 'warga.peta', 'match' => 'warga.peta'],
            ['icon' => 'bounding-box', 'text' => 'Bidang Tanah Saya', 'route' => 'warga.bidang', 'match' => 'warga.bidang*'],
            ['label' => 'Layanan Surat'],
            ['icon' => 'envelope-paper', 'text' => 'Ajukan & Lacak Surat', 'route' => 'warga.surat.index', 'match' => 'warga.surat.*'],
        ],
    };
@endphp

<aside class="app-sidebar">
    <div class="brand">
        <i class="bi bi-geo-alt-fill me-2"></i>SID {{ config('desa.nama') }}
    </div>
    <nav class="nav flex-column py-2">
        @foreach ($menu as $item)
            @isset($item['label'])
                <div class="nav-label">{{ $item['label'] }}</div>
            @else
                <a class="nav-link {{ isset($item['path']) ? (request()->is($item['path']) ? 'active' : '') : (request()->routeIs($item['match']) ? 'active' : '') }}" href="{{ route($item['route'], $item['params'] ?? []) }}">
                    <i class="bi bi-{{ $item['icon'] }}"></i>{{ $item['text'] }}
                </a>
            @endisset
        @endforeach
    </nav>
</aside>

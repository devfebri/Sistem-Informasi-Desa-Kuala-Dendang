<!doctype html>
<html lang="id" style="scroll-behavior:smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistem Informasi Desa {{ config('desa.nama') }}: data kependudukan, peta bidang tanah, dan pengajuan surat pertanahan secara online.">
    <title>Sistem Informasi Desa {{ config('desa.nama') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="landing">

{{-- ===== Navigasi ===== --}}
<nav class="navbar navbar-expand-lg sticky-top bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ url('/') }}">
            <i class="bi bi-geo-alt-fill text-primary fs-4"></i>
            <span class="lh-1">SID {{ config('desa.nama') }}<small class="d-block text-muted fw-normal" style="font-size:.7rem">Kab. {{ config('desa.kabupaten') }}</small></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama" aria-controls="menuUtama" aria-expanded="false" aria-label="Buka menu">
            <i class="bi bi-list fs-3"></i>
        </button>
        <div class="collapse navbar-collapse" id="menuUtama">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="#layanan">Layanan</a></li>
                <li class="nav-item"><a class="nav-link" href="#alur">Cara Mengajukan</a></li>
                <li class="nav-item"><a class="nav-link" href="#cek-surat">Cek Surat</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">Tanya Jawab</a></li>
                <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    @auth
                        <a class="btn btn-primary w-100" href="{{ route(auth()->user()->homeRoute()) }}"><i class="bi bi-speedometer2 me-1"></i>Ke Dashboard</a>
                    @else
                        <a class="btn btn-primary w-100" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</a>
                    @endauth
                </li>
            </ul>
        </div>
    </div>
</nav>

{{-- ===== Hero ===== --}}
<header class="landing-hero text-white">
    <div class="container py-5">
        <div class="row align-items-center g-4 py-lg-4">
            <div class="col-lg-7">
                <span class="badge rounded-pill text-bg-light text-primary mb-3"><i class="bi bi-patch-check-fill me-1"></i>Layanan resmi Pemerintah Desa</span>
                <h1 class="display-5 fw-bold mb-3">Urus surat tanah dari rumah, tanpa antre panjang.</h1>
                <p class="lead mb-4 opacity-90">
                    Lihat bidang tanah Anda di peta desa, ajukan surat keterangan pertanahan secara online,
                    dan pantau prosesnya sampai surat terbit.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    @auth
                        <a href="{{ route(auth()->user()->homeRoute()) }}" class="btn btn-light btn-lg text-primary fw-semibold"><i class="bi bi-speedometer2 me-2"></i>Buka Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-light btn-lg text-primary fw-semibold"><i class="bi bi-box-arrow-in-right me-2"></i>Masuk untuk Mengajukan</a>
                    @endauth
                    <a href="#alur" class="btn btn-outline-light btn-lg">Lihat caranya</a>
                </div>
                <div class="small mt-3 opacity-75"><i class="bi bi-info-circle me-1"></i>Belum punya akun? Datang ke kantor desa membawa KTP dan KK, petugas akan membuatkan akun Anda.</div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-lg border-0 text-dark">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <strong>Desa {{ config('desa.nama') }} dalam angka</strong>
                            <i class="bi bi-bar-chart-fill text-primary"></i>
                        </div>
                        <div class="row g-3 text-center">
                            @foreach ([
                                ['people-fill', number_format($angka['penduduk'], 0, ',', '.'), 'Penduduk'],
                                ['house-heart-fill', number_format($angka['kk'], 0, ',', '.'), 'Kepala Keluarga'],
                                ['bounding-box', number_format($angka['persil'], 0, ',', '.'), 'Bidang Tanah'],
                                ['rulers', number_format($angka['luas_ha'], 1, ',', '.'), 'Hektar Terpetakan'],
                            ] as [$ikon, $nilai, $label])
                                <div class="col-6">
                                    <div class="landing-stat rounded-3 p-3">
                                        <i class="bi bi-{{ $ikon }} text-primary fs-4"></i>
                                        <div class="fs-3 fw-bold lh-1 mt-1">{{ $nilai }}</div>
                                        <div class="small text-muted mt-1">{{ $label }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="small text-muted mt-3">Data agregat, tidak memuat data pribadi warga.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- ===== Layanan ===== --}}
<section id="layanan" class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Apa yang bisa Anda lakukan?</h2>
            <p class="text-muted mb-0">Semua layanan pertanahan desa dalam satu tempat.</p>
        </div>
        <div class="row g-3">
            @foreach ([
                ['map', 'Peta Bidang Tanah', 'Lihat letak dan batas bidang tanah di peta desa. Persil milik Anda ditandai khusus; data pemilik lain disamarkan.'],
                ['envelope-paper', 'Ajukan Surat Online', 'Unggah persyaratan dari ponsel, tanpa harus membawa berkas ke kantor desa pada pengajuan pertama.'],
                ['hourglass-split', 'Pantau Status', 'Ikuti proses pengajuan: diajukan, diverifikasi petugas, disetujui Kepala Desa, hingga surat terbit.'],
                ['file-earmark-pdf', 'Unduh Surat Resmi', 'Surat terbit berbentuk PDF dengan nomor surat, tanda tangan elektronik, dan QR code keaslian.'],
            ] as [$ikon, $judul, $isi])
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 landing-card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="landing-ikon mb-3"><i class="bi bi-{{ $ikon }}"></i></div>
                            <h3 class="h6 fw-bold">{{ $judul }}</h3>
                            <p class="small text-muted mb-0">{{ $isi }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($jenisSurat->isNotEmpty())
            <div class="text-center mt-4">
                <div class="small text-muted mb-2">Jenis surat yang dapat diajukan:</div>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach ($jenisSurat as $j)
                        <span class="badge rounded-pill text-bg-light border text-dark fw-normal px-3 py-2"><i class="bi bi-check2-circle text-success me-1"></i>{{ $j->nama }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>

{{-- ===== Alur ===== --}}
<section id="alur" class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Cara mengajukan surat</h2>
            <p class="text-muted mb-0">Empat langkah mudah.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['Masuk ke akun', 'Gunakan username dan kata sandi dari kantor desa. Belum punya? Minta dibuatkan oleh petugas.'],
                ['Pilih surat & bidang tanah', 'Pilih jenis surat dan bidang tanah milik Anda, lalu tulis keperluannya.'],
                ['Unggah persyaratan', 'Foto atau pindai KTP, KK, bukti kepemilikan, dan SPPT PBB sesuai jenis surat (PDF/JPG/PNG).'],
                ['Pantau & unduh', 'Petugas memverifikasi, Kepala Desa menyetujui. Setelah terbit, unduh suratnya langsung dari akun Anda.'],
            ] as $i => [$judul, $isi])
                <div class="col-sm-6 col-lg-3">
                    <div class="d-flex gap-3">
                        <div class="landing-langkah flex-shrink-0">{{ $i + 1 }}</div>
                        <div>
                            <h3 class="h6 fw-bold mb-1">{{ $judul }}</h3>
                            <p class="small text-muted mb-0">{{ $isi }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="text-center mt-4">
            @auth
                <a href="{{ route(auth()->user()->homeRoute()) }}" class="btn btn-primary">Ke Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk &amp; Mulai</a>
            @endauth
        </div>
    </div>
</section>

{{-- ===== Cek keaslian surat ===== --}}
<section id="cek-surat" class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm landing-cek">
                    <div class="card-body p-4 p-md-5">
                        <div class="row align-items-center g-4">
                            <div class="col-md-5 text-center text-md-start">
                                <i class="bi bi-qr-code-scan text-primary" style="font-size:3.5rem"></i>
                                <h2 class="h4 fw-bold mt-2">Cek keaslian surat</h2>
                                <p class="small text-muted mb-0">Terima surat dari desa? Pindai QR code di surat, atau ketik kode validasi di bawah QR.</p>
                            </div>
                            <div class="col-md-7">
                                <form action="{{ route('validasi.cari') }}" method="GET" data-loading novalidate>
                                    <label for="kode" class="form-label fw-semibold">Kode validasi</label>
                                    <div class="input-group input-group-lg">
                                        <input id="kode" name="kode" type="text" value="{{ old('kode') }}" maxlength="20"
                                               class="form-control text-uppercase @error('kode') is-invalid @enderror"
                                               placeholder="mis. A1B2C3D4E5F6" autocomplete="off" required>
                                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Periksa</button>
                                        @error('kode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="form-text">Kode terdiri dari 12 huruf/angka yang tercetak di bawah QR code pada surat.</div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== FAQ ===== --}}
<section id="faq" class="py-5 bg-white border-top">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="fw-bold text-center mb-4">Pertanyaan yang sering diajukan</h2>
                <div class="accordion" id="faqList">
                    @foreach ([
                        ['Siapa yang bisa mengajukan surat?', 'Warga yang sudah memiliki akun dan tercatat sebagai pemilik bidang tanah di data desa. Pengajuan hanya bisa dibuat untuk bidang tanah atas nama Anda sendiri.'],
                        ['Bagaimana cara mendapatkan akun?', 'Akun dibuat oleh petugas desa. Datanglah ke kantor desa dengan membawa KTP dan Kartu Keluarga, lalu petugas akan menghubungkan akun Anda dengan data kependudukan.'],
                        ['Dokumen apa yang harus disiapkan?', 'Umumnya fotokopi KTP, Kartu Keluarga, bukti kepemilikan tanah (sertifikat/girik/letter C), dan SPPT PBB terakhir. Daftar lengkapnya tampil otomatis saat Anda memilih jenis surat.'],
                        ['Bidang tanah saya belum muncul atau datanya keliru.', 'Hubungi petugas desa untuk pendataan atau perbaikan. Data pertanahan hanya dapat diubah oleh admin desa.'],
                        ['Bagaimana tahu surat saya asli?', 'Setiap surat memuat QR code dan kode validasi. Pindai QR atau masukkan kodenya pada kolom “Cek keaslian surat” di halaman ini.'],
                        ['Apakah data saya aman?', 'Berkas persyaratan disimpan privat dan hanya bisa dibuka oleh Anda dan petugas berwenang. Pada peta dan halaman validasi, nama pemilik disamarkan dan NIK tidak ditampilkan.'],
                    ] as $i => [$tanya, $jawab])
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button {{ $i ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}" aria-expanded="{{ $i ? 'false' : 'true' }}">{{ $tanya }}</button>
                            </h3>
                            <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" data-bs-parent="#faqList">
                                <div class="accordion-body text-muted">{{ $jawab }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== Kontak & footer ===== --}}
<footer id="kontak" class="landing-footer text-white pt-5 pb-3">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-geo-alt-fill fs-4"></i><strong class="fs-5">Pemerintah Desa {{ config('desa.nama') }}</strong></div>
                <div class="small opacity-75">Kecamatan {{ config('desa.kecamatan') }}, Kabupaten {{ config('desa.kabupaten') }}, {{ config('desa.provinsi') }}</div>
            </div>
            <div class="col-md-4">
                <h3 class="h6 fw-bold mb-2">Kantor Desa</h3>
                <ul class="list-unstyled small mb-0 opacity-90">
                    <li class="mb-1"><i class="bi bi-pin-map me-2"></i>{{ config('desa.alamat_kantor') }}, {{ config('desa.kode_pos') }}</li>
                    <li class="mb-1"><i class="bi bi-clock me-2"></i>{{ config('desa.jam_layanan') }}</li>
                    <li class="mb-1"><i class="bi bi-telephone me-2"></i>{{ config('desa.telepon') }}</li>
                    <li><i class="bi bi-envelope me-2"></i>{{ config('desa.email') }}</li>
                </ul>
            </div>
            <div class="col-md-3">
                <h3 class="h6 fw-bold mb-2">Tautan</h3>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><a href="#layanan" class="text-white-50 text-decoration-none">Layanan</a></li>
                    <li class="mb-1"><a href="#cek-surat" class="text-white-50 text-decoration-none">Cek keaslian surat</a></li>
                    <li><a href="{{ route('login') }}" class="text-white-50 text-decoration-none">Masuk</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-light opacity-25 my-4">
        <div class="small text-center opacity-75">&copy; {{ date('Y') }} Pemerintah Desa {{ config('desa.nama') }}. Sistem Informasi Desa Terintegrasi GIS.</div>
    </div>
</footer>

</body>
</html>

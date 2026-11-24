<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $p->nomor_surat }}</title>
    <style>
        @page { margin: 1.8cm 2.2cm 1.8cm 2.5cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.45; color: #000; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 6px; margin-bottom: 14px; }
        .kop .l1 { font-size: 13pt; text-transform: uppercase; }
        .kop .l2 { font-size: 15pt; font-weight: bold; text-transform: uppercase; }
        .kop .l3 { font-size: 10pt; font-style: italic; }
        .judul { text-align: center; margin: 10px 0 14px; }
        .judul .t { font-size: 13pt; font-weight: bold; text-decoration: underline; text-transform: uppercase; }
        .judul .n { font-size: 11.5pt; }
        p { margin: 0 0 8px; text-align: justify; }
        table.data { border-collapse: collapse; margin: 2px 0 8px 28px; }
        table.data td { padding: 1px 4px; vertical-align: top; }
        table.data td.k { width: 150px; }
        table.data td.s { width: 10px; }
        table.grid { border-collapse: collapse; width: 100%; margin: 4px 0 10px; font-size: 11pt; }
        table.grid th, table.grid td { border: 1px solid #000; padding: 3px 5px; text-align: left; vertical-align: top; }
        table.grid th { background: #eee; }
        .ttd { margin-top: 18px; width: 100%; page-break-inside: avoid; }
        .ttd td { vertical-align: top; }
        .ttd .kanan { width: 52%; text-align: center; }
        .kecil { font-size: 9.5pt; }
        .lampiran h4 { text-align: center; margin: 0 0 8px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="kop">
        <div class="l1">Pemerintah Kabupaten {{ $desa['kabupaten'] }}</div>
        <div class="l1">Kecamatan {{ $desa['kecamatan'] }}</div>
        <div class="l2">Pemerintah Desa {{ $desa['nama'] }}</div>
        <div class="l3">{{ $desa['alamat_kantor'] }}, Kode Pos {{ $desa['kode_pos'] }}</div>
    </div>

    <div class="judul">
        <div class="t">@yield('judul')</div>
        <div class="n">Nomor: {{ $p->nomor_surat }}</div>
    </div>

    @yield('isi')

    <table class="ttd">
        <tr>
            <td>
                <img src="{{ $qr }}" width="85" height="85" alt="QR">
                <div class="kecil">Pindai untuk memeriksa keaslian surat<br>Kode: {{ $p->kode_validasi }}</div>
            </td>
            <td class="kanan">
                {{ $desa['nama'] }}, {{ $p->approved_at->translatedFormat('d F Y') }}<br>
                {{ $desa['jabatan_kades'] }} {{ $desa['nama'] }}
                <div class="kecil" style="margin:14px 0">[ Ditandatangani secara elektronik ]</div>
                <strong><u>{{ $p->penyetuju?->name ?? $desa['kepala_desa'] }}</u></strong>
            </td>
        </tr>
    </table>

    @if ($sketsa)
        <div class="lampiran" style="page-break-before: always;">
            <h4>Lampiran: Sketsa Lokasi Persil {{ $bidang->no_persil }}</h4>
            <img src="{{ $sketsa }}" style="width:100%" alt="Sketsa lokasi">
            <p class="kecil" style="margin-top:6px">
                Sketsa dibuat otomatis dari data spasial pertanahan Desa {{ $desa['nama'] }} (bukan hasil ukur resmi).
                Persil berwarna biru adalah objek surat; persil berwarna abu-abu adalah persil di sekitarnya.
            </p>
        </div>
    @endif
</body>
</html>

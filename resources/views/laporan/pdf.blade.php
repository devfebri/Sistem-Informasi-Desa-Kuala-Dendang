<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $l->judul() }}</title>
    <style>
        @page { margin: 1.4cm 1.5cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 9.5pt; color: #000; }
        .kop { text-align: center; border-bottom: 2px solid #000; padding-bottom: 4px; margin-bottom: 8px; }
        .kop .a { font-size: 11pt; text-transform: uppercase; }
        .kop .b { font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        h2 { text-align: center; font-size: 12pt; margin: 4px 0 2px; text-transform: uppercase; }
        .sub { text-align: center; font-size: 9.5pt; margin-bottom: 8px; }
        table.ring { border-collapse: collapse; margin-bottom: 8px; }
        table.ring td { padding: 1px 10px 1px 0; font-size: 9.5pt; }
        table.grid { border-collapse: collapse; width: 100%; table-layout: fixed; }
        table.grid th, table.grid td { border: 1px solid #000; padding: 2px 4px; vertical-align: top; word-wrap: break-word; font-size: 8.5pt; }
        table.grid th { background: #e9e9e9; text-align: center; }
        table.grid tr.baru td { border-top: 2px solid #000; }
        .catatan { font-size: 8.5pt; font-style: italic; margin-top: 4px; }
        .ttd { width: 100%; margin-top: 14px; page-break-inside: avoid; }
        .ttd td { vertical-align: top; font-size: 10pt; }
        .foot { position: fixed; bottom: -0.6cm; left: 0; right: 0; font-size: 8pt; color: #555; text-align: right; }
    </style>
</head>
<body>
    <div class="kop">
        <div class="a">Pemerintah Kabupaten {{ $desa['kabupaten'] }} · Kecamatan {{ $desa['kecamatan'] }}</div>
        <div class="b">Pemerintah Desa {{ $desa['nama'] }}</div>
    </div>

    <h2>{{ $l->judul() }}</h2>
    <div class="sub">{{ $deskripsi }}</div>

    @if ($ringkasan)
        <table class="ring">
            <tr>
                @foreach ($ringkasan as $k => $v)
                    <td><strong>{{ $k }}:</strong> {{ $v }}</td>
                @endforeach
            </tr>
        </table>
    @endif

    @php($lebar = $l->lebarKolom())
    @php($kel = $l->kelompok())
    <table class="grid">
        <colgroup>
            <col style="width:3%">
            @foreach ($l->kolom() as $i => $_)
                <col style="width:{{ $lebar[$i] ?? floor(97 / count($l->kolom())) }}%">
            @endforeach
        </colgroup>
        <thead>
            <tr>
                <th>No</th>
                @foreach ($l->kolom() as $k)<th>{{ $k }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
            @php($sebelumnya = null)
            @forelse ($baris as $n => $r)
                @php($baru = $kel !== null && $r[$kel] !== $sebelumnya)
                <tr class="{{ $baru && $n > 0 ? 'baru' : '' }}">
                    <td style="text-align:center">{{ $n + 1 }}</td>
                    @foreach ($r as $i => $v)
                        <td>{{ $kel === $i && ! $baru ? '' : $v }}</td>
                    @endforeach
                </tr>
                @php($sebelumnya = $kel !== null ? $r[$kel] : null)
            @empty
                <tr><td colspan="{{ count($l->kolom()) + 1 }}" style="text-align:center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($terpotong)
        <div class="catatan">Catatan: PDF dibatasi {{ number_format($batas, 0, ',', '.') }} baris pertama dari total {{ number_format($total, 0, ',', '.') }} baris. Gunakan ekspor Excel untuk data lengkap.</div>
    @endif

    <table class="ttd">
        <tr>
            <td style="width:60%"></td>
            <td style="text-align:center">
                {{ $desa['nama'] }}, {{ now()->translatedFormat('d F Y') }}<br>
                Mengetahui,<br>{{ $desa['jabatan_kades'] }} {{ $desa['nama'] }}
                <br><br><br><br>
                <strong><u>{{ $desa['kepala_desa'] }}</u></strong>
            </td>
        </tr>
    </table>

    <div class="foot">Dicetak oleh {{ $pencetak }} · {{ now()->format('d-m-Y H:i') }}</div>
</body>
</html>

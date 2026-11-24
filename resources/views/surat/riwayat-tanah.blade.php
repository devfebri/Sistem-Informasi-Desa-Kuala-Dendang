@extends('surat.layout')
@section('judul', 'Surat Keterangan Riwayat Tanah')

@section('isi')
<p>Yang bertanda tangan di bawah ini, {{ $desa['jabatan_kades'] }} {{ $desa['nama'] }}, Kecamatan {{ $desa['kecamatan'] }}, Kabupaten {{ $desa['kabupaten'] }}, menerangkan bahwa sebidang tanah:</p>
@include('surat._bidang')
<p>saat ini tercatat atas nama:</p>
@include('surat._pemohon')

<p>Adapun riwayat peralihan hak atas tanah tersebut berdasarkan catatan Pemerintah Desa adalah sebagai berikut:</p>
@if ($riwayat->isEmpty())
    <p style="margin-left:28px"><em>Tidak terdapat catatan peralihan hak. Tanah tersebut dikuasai oleh pemilik saat ini sebagaimana tercantum di atas.</em></p>
@else
    <table class="grid">
        <thead>
            <tr><th style="width:24px">No</th><th>Tanggal</th><th>Dari</th><th>Kepada</th><th>Dasar</th><th>No. Akta</th></tr>
        </thead>
        <tbody>
            @foreach ($riwayat as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r->tanggal->translatedFormat('d F Y') }}</td>
                    <td>{{ $r->pemilikLama?->nama ?? $r->nik_pemilik_lama }}</td>
                    <td>{{ $r->pemilikBaru?->nama ?? $r->nik_pemilik_baru }}</td>
                    <td>{{ $r->jenisLabel() }}</td>
                    <td>{{ $r->no_akta ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<p>Surat keterangan ini dibuat atas permintaan yang bersangkutan untuk keperluan <strong>{{ $p->keperluan }}</strong>. Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
@endsection

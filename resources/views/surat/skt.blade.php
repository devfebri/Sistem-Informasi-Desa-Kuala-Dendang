@extends('surat.layout')
@section('judul', 'Surat Keterangan Tanah')

@section('isi')
<p>Yang bertanda tangan di bawah ini, {{ $desa['jabatan_kades'] }} {{ $desa['nama'] }}, Kecamatan {{ $desa['kecamatan'] }}, Kabupaten {{ $desa['kabupaten'] }}, menerangkan bahwa:</p>
@include('surat._pemohon')
<p>Berdasarkan data pertanahan yang tercatat pada Pemerintah Desa {{ $desa['nama'] }}, orang tersebut di atas benar memiliki/menguasai sebidang tanah dengan keterangan sebagai berikut:</p>
@include('surat._bidang')
<p>Surat keterangan ini dibuat atas permintaan yang bersangkutan untuk keperluan <strong>{{ $p->keperluan }}</strong>.</p>
<p>Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
@endsection

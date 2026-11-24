@extends('surat.layout')
@section('judul', 'Surat Keterangan Jual Beli')

@section('isi')
<p>Yang bertanda tangan di bawah ini, {{ $desa['jabatan_kades'] }} {{ $desa['nama'] }}, Kecamatan {{ $desa['kecamatan'] }}, Kabupaten {{ $desa['kabupaten'] }}, menerangkan bahwa:</p>
@include('surat._pemohon')
<p>adalah pemilik sah atas sebidang tanah yang tercatat pada Pemerintah Desa {{ $desa['nama'] }} dengan keterangan sebagai berikut:</p>
@include('surat._bidang')
@if ($riwayat->isNotEmpty())
    <p>Tanah tersebut diperoleh melalui {{ strtolower($riwayat->last()->jenisLabel()) }} pada tanggal {{ $riwayat->last()->tanggal->translatedFormat('d F Y') }}{{ $riwayat->last()->no_akta ? ' (No. akta '.$riwayat->last()->no_akta.')' : '' }}.</p>
@endif
<p>Berdasarkan data yang ada, tanah tersebut benar milik yang bersangkutan dan <strong>tidak sedang dalam sengketa</strong>, sehingga dapat dialihkan haknya melalui jual beli sepanjang memenuhi ketentuan peraturan perundang-undangan yang berlaku.</p>
<p>Surat keterangan ini dibuat atas permintaan yang bersangkutan untuk keperluan <strong>{{ $p->keperluan }}</strong>. Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
@endsection

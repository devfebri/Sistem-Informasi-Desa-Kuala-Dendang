@extends('surat.layout')
@section('judul', 'Surat Keterangan Tidak Sengketa')

@section('isi')
<p>Yang bertanda tangan di bawah ini, {{ $desa['jabatan_kades'] }} {{ $desa['nama'] }}, Kecamatan {{ $desa['kecamatan'] }}, Kabupaten {{ $desa['kabupaten'] }}, menerangkan bahwa:</p>
@include('surat._pemohon')
<p>adalah pemilik/penguasa sebidang tanah dengan keterangan sebagai berikut:</p>
@include('surat._bidang')
<p>Berdasarkan data dan catatan yang ada pada Pemerintah Desa {{ $desa['nama'] }}, serta sepanjang pengetahuan kami, tanah tersebut <strong>tidak sedang dalam sengketa</strong> dengan pihak mana pun dan tidak dalam keadaan dijadikan jaminan.</p>
<p>Surat keterangan ini dibuat atas permintaan yang bersangkutan untuk keperluan <strong>{{ $p->keperluan }}</strong>. Apabila di kemudian hari terbukti keterangan ini tidak benar, maka yang bersangkutan bersedia dituntut sesuai peraturan perundang-undangan yang berlaku.</p>
<p>Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
@endsection

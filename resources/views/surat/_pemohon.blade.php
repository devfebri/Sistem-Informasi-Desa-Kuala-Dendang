<table class="data">
    <tr><td class="k">Nama</td><td class="s">:</td><td><strong>{{ $pemohon->nama }}</strong></td></tr>
    <tr><td class="k">NIK</td><td class="s">:</td><td>{{ $pemohon->nik }}</td></tr>
    <tr><td class="k">Tempat/Tgl. Lahir</td><td class="s">:</td><td>{{ $pemohon->tempat_lahir }}, {{ $pemohon->tanggal_lahir->translatedFormat('d F Y') }}</td></tr>
    <tr><td class="k">Pekerjaan</td><td class="s">:</td><td>{{ $pemohon->pekerjaan ?: '-' }}</td></tr>
    <tr><td class="k">Alamat</td><td class="s">:</td><td>{{ $pemohon->alamat ?: '-' }}</td></tr>
</table>

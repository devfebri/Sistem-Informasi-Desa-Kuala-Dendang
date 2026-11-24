<table class="data">
    <tr><td class="k">No. Persil</td><td class="s">:</td><td><strong>{{ $bidang->no_persil }}</strong></td></tr>
    <tr><td class="k">Luas</td><td class="s">:</td><td>&plusmn; {{ number_format($bidang->luas_m2, 0, ',', '.') }} m&sup2;</td></tr>
    <tr><td class="k">Status hak</td><td class="s">:</td><td>{{ $bidang->status_hak }}{{ $bidang->no_sertifikat ? ' (No. '.$bidang->no_sertifikat.')' : '' }}</td></tr>
    <tr><td class="k">Penggunaan</td><td class="s">:</td><td>{{ ucfirst($bidang->penggunaan_lahan) }}</td></tr>
    <tr><td class="k">Letak</td><td class="s">:</td><td>{{ $bidang->wilayah?->label ?? '-' }}, Desa {{ $desa['nama'] }}, Kecamatan {{ $desa['kecamatan'] }}, Kabupaten {{ $desa['kabupaten'] }}</td></tr>
    <tr><td class="k">Batas utara</td><td class="s">:</td><td>{{ $bidang->batas_utara ?: '-' }}</td></tr>
    <tr><td class="k">Batas selatan</td><td class="s">:</td><td>{{ $bidang->batas_selatan ?: '-' }}</td></tr>
    <tr><td class="k">Batas timur</td><td class="s">:</td><td>{{ $bidang->batas_timur ?: '-' }}</td></tr>
    <tr><td class="k">Batas barat</td><td class="s">:</td><td>{{ $bidang->batas_barat ?: '-' }}</td></tr>
</table>

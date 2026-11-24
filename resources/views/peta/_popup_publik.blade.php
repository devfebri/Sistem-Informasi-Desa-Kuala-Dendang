<div style="min-width:220px">
    <h6 class="mb-1">{{ $b->no_persil }} @if ($milikSaya)<span class="badge text-bg-primary">Milik Anda</span>@endif</h6>
    <table class="table table-sm table-borderless mb-2" style="font-size:.85rem">
        <tr><td class="text-muted ps-0">Pemilik</td><td>{{ \App\Support\Samar::nama($b->pemilik?->nama) ?: '-' }}</td></tr>
        <tr><td class="text-muted ps-0">Luas</td><td>{{ number_format($b->luas_m2, 0, ',', '.') }} m²</td></tr>
        <tr><td class="text-muted ps-0">Status hak</td><td>{{ $b->status_hak }}</td></tr>
        <tr><td class="text-muted ps-0">Penggunaan</td><td>{{ ucfirst($b->penggunaan_lahan) }}</td></tr>
        <tr><td class="text-muted ps-0">Dusun/RT</td><td>{{ $b->wilayah?->label ?? '-' }}</td></tr>
    </table>
    @if ($milikSaya)
        <a class="btn btn-sm btn-primary w-100" href="{{ route('warga.surat.index') }}">Ajukan surat untuk bidang ini</a>
    @endif
</div>

<a href="{{ route('admin.bidang-tanah.show', $id) }}" class="btn btn-sm btn-info text-white" title="Detail">
    <i class="bi bi-eye"></i>
</a>
<a href="{{ route('admin.bidang-tanah.edit', $id) }}" class="btn btn-sm btn-warning" title="Ubah">
    <i class="bi bi-pencil-square"></i>
</a>
<button type="button" class="btn btn-sm btn-danger btn-hapus" data-id="{{ $id }}" title="Hapus">
    <i class="bi bi-trash"></i>
</button>

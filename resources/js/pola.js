// Pola reusable: modal form AJAX, konfirmasi hapus, spinner tombol.

/** Tampilkan / sembunyikan spinner pada tombol dan nonaktifkan agar tidak dobel klik. */
export function setLoading($btn, loading) {
    $btn.each(function () {
        const $b = $(this);
        if (loading) {
            $b.data('label', $b.html()).prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1"></span>Memproses...');
        } else {
            $b.prop('disabled', false).html($b.data('label') ?? $b.html());
        }
    });
}

/** Data form untuk $.ajax: otomatis multipart (FormData) bila ada input file. */
function payloadForm($form, method = null) {
    if ($form.find('input[type="file"]').length) {
        const fd = new FormData($form[0]);
        if (method) fd.append('_method', method);
        return { data: fd, processData: false, contentType: false };
    }
    const data = $form.serializeArray();
    if (method) data.push({ name: '_method', value: method });
    return { data: $.param(data) };
}

function clearErrors($form) {
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback.js').remove();
}

function showErrors($form, errors) {
    $.each(errors, (field, msgs) => {
        // "password" dan "password_confirmation" ditampilkan pada input yang sama.
        // "dokumen.0" (validasi Laravel) -> name="dokumen[0]" pada form
        const bracket = field.replace(/\.(\w+)/g, '[$1]');
        const $input = $form.find(`[name="${field}"], [name="${bracket}"]`).first();
        if (!$input.length) return;
        $input.addClass('is-invalid');
        const $s2 = $input.next('.select2-container');
        const $box = $s2.length ? $s2 : ($input.closest('.input-group').length ? $input.closest('.input-group') : $input);
        $('<div class="invalid-feedback js d-block"></div>').text(msgs[0]).insertAfter($box);
    });
}

/**
 * Modal form AJAX untuk tambah/edit.
 *
 * AppModalForm({
 *   modal: '#modalForm',            // elemen modal (berisi <form>)
 *   table: dataTableInstance,       // di-reload setelah sukses (tetap di halaman yang sama)
 *   storeUrl: '/admin/pengguna',
 *   updateUrl: id => `/admin/pengguna/${id}`,
 *   editUrl: id => `/admin/pengguna/${id}/edit`,   // opsional: ambil data lewat AJAX
 *   fill: (data, $form) => {},      // opsional: isi field khusus
 *   reset: ($form) => {},           // opsional: reset khusus saat tambah
 * })
 * Mengembalikan { create(), edit(id) }.
 */
export function AppModalForm(opt) {
    const $modal = $(opt.modal);
    const $form = $modal.find('form');
    const modal = window.bootstrap.Modal.getOrCreateInstance($modal[0]);
    const $title = $modal.find('.modal-title');
    let editingId = null;

    function open(title) {
        clearErrors($form);
        $title.text(title);
        modal.show();
    }

    const api = {
        create() {
            editingId = null;
            $form[0].reset();
            opt.reset?.($form);
            open($modal.data('title-create') || 'Tambah Data');
        },
        edit(id) {
            editingId = id;
            $form[0].reset();
            $.get(opt.editUrl(id)).done((data) => {
                $.each(data, (k, v) => {
                    const $f = $form.find(`[name="${k}"]`);
                    if ($f.is(':checkbox')) $f.prop('checked', !!v);
                    else if (!$f.is('[type="password"]')) $f.val(typeof v === 'boolean' ? Number(v) : v);
                });
                opt.fill?.(data, $form);
                open($modal.data('title-edit') || 'Ubah Data');
            });
        },
    };

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors($form);
        const $btn = $form.find('[type="submit"]');
        const isEdit = editingId !== null;

        setLoading($btn, true);
        $.ajax({
            url: isEdit ? opt.updateUrl(editingId) : opt.storeUrl,
            method: 'POST',
            ...payloadForm($form, isEdit ? 'PUT' : null),
        }).done((res) => {
            modal.hide();
            window.toastr.success(res.message || 'Data berhasil disimpan.');
            opt.table?.ajax.reload(null, false);
            opt.onSuccess?.(res);
        }).fail((xhr) => {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                showErrors($form, xhr.responseJSON.errors);
            } else if (xhr.status === 422) {
                window.toastr.error(xhr.responseJSON?.message);
            }
        }).always(() => setLoading($btn, false));
    });

    return api;
}

/**
 * Form halaman penuh yang dikirim via AJAX (mis. form bidang tanah dengan peta).
 * Respons sukses: { message, redirect }.
 */
export function AppAjaxForm(formSelector) {
    const $form = $(formSelector);
    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors($form);
        const $btn = $form.find('[type="submit"]');
        setLoading($btn, true);
        $.ajax({ url: $form.attr('action'), method: 'POST', ...payloadForm($form) })
            .done((res) => {
                window.toastr.success(res.message || 'Data berhasil disimpan.');
                if (res.redirect) {
                    setTimeout(() => { window.location.href = res.redirect; }, 700);
                } else {
                    setLoading($btn, false);
                    if ($form.is('[data-reset]')) $form[0].reset();
                }
            })
            .fail((xhr) => {
                setLoading($btn, false);
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors($form, xhr.responseJSON.errors);
                    window.toastr.error('Periksa kembali isian yang bertanda merah.');
                }
            });
    });
}

/** Konfirmasi hapus (SweetAlert2) lalu DELETE via AJAX. */
export function confirmDelete(url, table, text = 'Data yang dihapus tidak dapat dikembalikan.') {
    window.Swal.fire({
        title: 'Hapus data?',
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
    }).then((r) => {
        if (!r.isConfirmed) return;
        $.ajax({ url, method: 'POST', data: { _method: 'DELETE' } })
            .done((res) => {
                window.toastr.success(res.message || 'Data berhasil dihapus.');
                table?.ajax.reload(null, false);
            })
            .fail((xhr) => window.toastr.error(xhr.responseJSON?.message || 'Gagal menghapus data.'));
    });
}

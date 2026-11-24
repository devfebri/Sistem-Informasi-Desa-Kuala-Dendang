// DataTables server-side + Select2 AJAX (dimuat hanya di halaman yang memakainya).
import DataTable from 'datatables.net-bs5';
import select2 from 'select2';

select2(window, window.$);
window.DataTable = DataTable;

const bahasa = {
    processing: 'Memuat data...',
    search: 'Cari:',
    searchPlaceholder: 'Ketik untuk mencari...',
    lengthMenu: 'Tampilkan _MENU_ data',
    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
    infoEmpty: 'Tidak ada data',
    infoFiltered: '(disaring dari _MAX_ data)',
    zeroRecords: 'Data tidak ditemukan',
    emptyTable: 'Belum ada data',
    paginate: { first: '«', last: '»', next: '›', previous: '‹' },
};

/**
 * Tabel AJAX server-side standar: 10 baris, opsi 10/25/50/100, pencarian debounce 400 ms.
 *
 * const table = AppTable('#tabel', {
 *   ajax: { url: '...', data: d => { d.role = $('#filterRole').val(); } },
 *   columns: [...],
 * });
 * Filter eksternal: panggil table.ajax.reload() saat filter berubah.
 */
window.AppTable = function (selector, options = {}) {
    const $el = window.$(selector);
    const table = new DataTable($el[0], {
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        searchDelay: 400, // debounce pencarian
        language: bahasa,
        layout: {
            topStart: 'pageLength',
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging',
        },
        ...options,
        // Tampilkan skeleton sebelum data pertama tiba.
        initComplete() { $el.find('tbody .skeleton-row').remove(); options.initComplete?.apply(this, arguments); },
    });
    return table;
};

/** Baris skeleton awal (panggil sebelum AppTable). */
window.tableSkeleton = function (selector, kolom, baris = 5) {
    const tr = '<tr class="skeleton-row">' + '<td></td>'.repeat(kolom) + '</tr>';
    window.$(selector).find('tbody').html(tr.repeat(baris));
};

/**
 * Select2 AJAX dengan pencarian + pagination.
 * AppSelect2('#nik', { url: '/api/penduduk/cari', placeholder: 'Cari NIK / nama', dropdownParent: '#modalForm' })
 * Endpoint harus mengembalikan { results: [{id, text}], pagination: { more: bool } }.
 */
window.AppSelect2 = function (selector, { url, placeholder = 'Cari...', dropdownParent = null, minimumInputLength = 2 }) {
    const $el = window.$(selector);
    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder,
        allowClear: true,
        minimumInputLength,
        // Hasil dengan field "sub" ditampilkan dua baris (mis. NIK — nama / alamat).
        templateResult: (d) => {
            if (!d.id || !d.sub) return d.text;
            return window.$('<div></div>')
                .append(window.$('<div></div>').text(d.text))
                .append(window.$('<small class="text-muted"></small>').text(d.sub));
        },
        dropdownParent: dropdownParent ? window.$(dropdownParent) : undefined,
        language: {
            inputTooShort: () => `Ketik minimal ${minimumInputLength} karakter`,
            searching: () => 'Mencari...',
            noResults: () => 'Tidak ditemukan',
            loadingMore: () => 'Memuat lainnya...',
        },
        ajax: {
            url,
            delay: 400,
            dataType: 'json',
            data: (p) => ({ q: p.term, page: p.page || 1 }),
        },
    });
    return $el;
};

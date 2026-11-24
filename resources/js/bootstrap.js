// Inti: jQuery, Bootstrap, notifikasi, progress bar, CSRF AJAX global.
import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import toastr from 'toastr';
import NProgress from 'nprogress';

import 'bootstrap-icons/font/bootstrap-icons.css';
import 'toastr/build/toastr.min.css';
import 'nprogress/nprogress.css';

window.$ = window.jQuery = $;
window.bootstrap = bootstrap;
window.Swal = Swal;
window.toastr = toastr;
window.NProgress = NProgress;

toastr.options = { positionClass: 'toast-top-right', timeOut: 3500, closeButton: true, progressBar: true };
NProgress.configure({ showSpinner: false, trickleSpeed: 120 });

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

// Progress bar tipis untuk setiap request AJAX.
let aktif = 0;
$(document).on('ajaxSend', () => { if (aktif++ === 0) NProgress.start(); });
$(document).on('ajaxComplete', () => { if (--aktif <= 0) { aktif = 0; NProgress.done(); } });

// Sesi habis / tidak berhak.
$(document).ajaxError((e, xhr) => {
    if (xhr.status === 401 || xhr.status === 419) {
        toastr.warning('Sesi Anda berakhir. Silakan masuk kembali.');
        setTimeout(() => (window.location.href = '/login'), 1200);
    } else if (xhr.status === 403) {
        toastr.error(xhr.responseJSON?.message || 'Anda tidak memiliki hak akses.');
    } else if (xhr.status >= 500) {
        toastr.error('Terjadi kesalahan pada server.');
    }
});

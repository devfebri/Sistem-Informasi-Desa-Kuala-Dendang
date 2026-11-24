import './bootstrap';
import { AppModalForm, AppAjaxForm, confirmDelete, setLoading } from './pola';

window.AppModalForm = AppModalForm;
window.AppAjaxForm = AppAjaxForm;
window.confirmDelete = confirmDelete;
window.setLoading = setLoading;

// Sidebar mobile.
$(document).on('click', '[data-toggle-sidebar], .sidebar-backdrop', () => {
    $('body').toggleClass('sidebar-open');
});

// Spinner pada tombol submit untuk form biasa (mis. login) agar tidak dobel klik.
$(document).on('submit', 'form[data-loading]', function () {
    setLoading($(this).find('[type="submit"]'), true);
});

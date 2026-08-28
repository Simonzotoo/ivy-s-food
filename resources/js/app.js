import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[char]));
}

window.ivyToast = function ivyToast(message) {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#d9110f',
        timer: 3000,
        timerProgressBar: true,
    });
};

window.ivyError = function ivyError(messages) {
    const list = Array.isArray(messages) ? messages : [messages];

    Swal.fire({
        icon: 'error',
        title: list.length > 1 ? 'Please fix the following' : 'Something went wrong',
        html: list.length > 1
            ? '<ul style="text-align:left;margin:0;padding-left:1.2em;">' + list.map((m) => `<li>${escapeHtml(m)}</li>`).join('') + '</ul>'
            : escapeHtml(list[0]),
        confirmButtonColor: '#d9110f',
    });
};

window.ivyConfirm = function ivyConfirm(message, confirmLabel) {
    return Swal.fire({
        icon: 'warning',
        text: message,
        showCancelButton: true,
        confirmButtonText: confirmLabel || 'Yes, continue',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d9110f',
        cancelButtonColor: '#2d4054',
        reverseButtons: true,
    }).then((result) => result.isConfirmed);
};

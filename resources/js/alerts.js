import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import './alerts.css';

const popupClasses = {
    popup: 'spes-swal-popup',
    title: 'spes-swal-title',
    confirmButton: 'spes-swal-confirm',
    cancelButton: 'spes-swal-cancel',
};

const themeValue = (property, fallback) => {
    const value = getComputedStyle(document.documentElement).getPropertyValue(property).trim();
    return value || getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || fallback;
};

const fire = (options = {}) => Swal.fire({
    buttonsStyling: false,
    confirmButtonColor: themeValue('--primary', '#8b0000'),
    cancelButtonColor: 'transparent',
    customClass: popupClasses,
    showClass: { popup: 'spes-swal-enter' },
    hideClass: { popup: 'spes-swal-leave' },
    ...options,
});

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4500,
    timerProgressBar: true,
    customClass: { popup: 'spes-swal-toast' },
    showClass: { popup: 'spes-swal-enter' },
    hideClass: { popup: 'spes-swal-leave' },
});

const alerts = {
    success(message, title = 'Success') {
        return fire({ icon: 'success', title, text: message });
    },
    error(message, title = 'Unable to complete request') {
        return fire({ icon: 'error', title, text: message });
    },
    warning(message, title = 'Please review') {
        return fire({ icon: 'warning', title, text: message });
    },
    confirm(message, options = {}) {
        return fire({
            icon: options.icon || 'warning',
            title: options.title || 'Please confirm',
            text: message,
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || 'Confirm',
            cancelButtonText: options.cancelButtonText || 'Cancel',
            reverseButtons: true,
            focusCancel: true,
        }).then((result) => result.isConfirmed);
    },
    loading(message = 'Please wait...') {
        return fire({
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });
    },
    close() {
        Swal.close();
    },
    authorizeSubmit(form) {
        confirmedSubmissions.add(form);
    },
    toast: {
        success(message) {
            return toast.fire({ icon: 'success', text: message });
        },
        error(message) {
            return toast.fire({ icon: 'error', text: message });
        },
        warning(message) {
            return toast.fire({ icon: 'warning', text: message });
        },
        info(message) {
            return toast.fire({ icon: 'info', text: message });
        },
    },
};

window.spesAlerts = alerts;

const confirmationsInFlight = new WeakSet();
const confirmedSubmissions = new WeakSet();

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    if (confirmedSubmissions.has(form)) {
        confirmedSubmissions.delete(form);
        return;
    }

    const submitter = event.submitter;
    const confirmation = form.dataset.swalConfirm || submitter?.dataset.swalConfirm;
    if (confirmation) {
        event.preventDefault();
        event.stopImmediatePropagation();
        if (confirmationsInFlight.has(form)) return;

        confirmationsInFlight.add(form);
        try {
            const isConfirmed = await alerts.confirm(confirmation, {
                title: form.dataset.swalTitle || 'Please confirm',
                confirmButtonText: form.dataset.swalConfirmAction || 'Continue',
                cancelButtonText: form.dataset.swalCancelAction || 'Cancel',
            });
            if (!isConfirmed) return;
            if (!form.noValidate && !submitter?.formNoValidate && !form.reportValidity()) return;

            confirmedSubmissions.add(form);
            if (submitter) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
        } finally {
            confirmationsInFlight.delete(form);
        }
        return;
    }

    const loadingMessage = form.dataset.swalLoading;
    if (!loadingMessage) return;
    if (form.dataset.swalLoadingActive) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return;
    }

    if (loadingMessage) {
        form.dataset.swalLoadingActive = 'true';
        alerts.loading(loadingMessage);
    }
}, true);

const flashKinds = new Set(['success', 'error', 'warning', 'info']);
const shownFlashes = new Set();
document.querySelectorAll('[data-spes-alert]').forEach((element) => {
    const kind = element.dataset.spesAlert;
    const message = element.textContent.trim();
    if (!flashKinds.has(kind) || !message) return;

    element.remove();
    const key = `${kind}:${message}`;
    if (shownFlashes.has(key)) return;
    shownFlashes.add(key);
    alerts.toast[kind](message);
});

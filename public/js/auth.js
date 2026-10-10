// Auth Form Validation and Effects
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('[data-auth-form]') || document.querySelector('form');
    
    if (form) {
        const submitBtn = form.querySelector('.btn-submit');
        const authSubmitBtn = form.querySelector('[data-auth-submit]');
        const buttonLabel = form.querySelector('[data-auth-button-label]');
        const spinner = form.querySelector('[data-auth-spinner]');
        const requestStatus = form.querySelector('[data-auth-request-status]');
        const termsCheckbox = form.querySelector('#terms_accepted');
        const termsError = document.getElementById('terms-error');
        const initialButtonLabel = buttonLabel?.textContent.trim() ?? '';

        if (buttonLabel) {
            buttonLabel.dataset.initialLabel = initialButtonLabel;
        }

        if (termsCheckbox && submitBtn) {
            submitBtn.disabled = !termsCheckbox.checked;

            termsCheckbox.addEventListener('change', function() {
                submitBtn.disabled = !this.checked || form.dataset.submissionLocked === 'true';
                if (this.checked && termsError) {
                    termsError.hidden = true;
                }
            });

            termsCheckbox.addEventListener('invalid', function() {
                if (termsError) {
                    termsError.hidden = false;
                    termsError.textContent = 'Please agree to the Terms and Conditions before continuing.';
                }
            });
        }

        form.addEventListener('submit', function(e) {
            if (form.hasAttribute('data-auth-form')) {
                e.preventDefault();

                if (form.dataset.submissionLocked === 'true') {
                    return;
                }

                form.dataset.submissionLocked = 'true';
                form.setAttribute('aria-busy', 'true');

                if (authSubmitBtn) {
                    authSubmitBtn.disabled = true;
                    authSubmitBtn.classList.add('is-loading');
                    authSubmitBtn.setAttribute('aria-busy', 'true');
                }

                if (buttonLabel) {
                    buttonLabel.textContent = form.dataset.loadingLabel;
                }

                if (spinner) {
                    spinner.hidden = false;
                }

                if (requestStatus) {
                    requestStatus.textContent = form.dataset.loadingMessage;
                    requestStatus.hidden = false;
                    requestStatus.classList.remove('is-error', 'is-success');
                }

                void submitAuthForm(form);
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.7';
            }
        });

        // Real-time form validation
        const inputs = form.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });

            input.addEventListener('input', function() {
                if (this.type === 'password' || this.classList.contains('error')) {
                    validateField(this);
                }

                if (this.name === 'password') {
                    const confirmation = form.querySelector('input[name="password_confirmation"]');
                    if (confirmation && confirmation.value !== '') {
                        validateField(confirmation);
                    }
                }
            });
        });
    }
});

async function submitAuthForm(form) {
    try {
        const response = await fetch(form.action, {
            method: form.method.toUpperCase(),
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const contentType = response.headers.get('content-type') ?? '';
        const responseBody = contentType.includes('application/json')
            ? await response.json()
            : await response.text();

        if (!response.ok) {
            if (response.status >= 500 && await recoverAuthRequest(form)) {
                return;
            }
            const errorMessage = response.status >= 500
                ? form.dataset.serverError
                : extractAuthErrors(responseBody) || form.dataset.serverError;
            restoreAuthForm(form, errorMessage, 'error');
            return;
        }

        if (typeof responseBody?.redirect === 'string') {
            const destination = new URL(responseBody.redirect, window.location.href);
            if (destination.origin !== window.location.origin) {
                restoreAuthForm(form, form.dataset.serverError, 'error');
                return;
            }

            if (destination.href !== window.location.href) {
                window.location.assign(destination.href);
                return;
            }

            restoreAuthForm(
                form,
                extractAuthErrors(responseBody) || form.dataset.validationError,
                'error'
            );
            return;
        }

        if (typeof responseBody === 'string' && responseBody !== '') {
            restoreAuthForm(form, form.dataset.serverError, 'error');
            return;
        }

        restoreAuthForm(form, form.dataset.successMessage, 'success');
    } catch {
        const recovered = await recoverAuthRequest(form);
        if (recovered) {
            return;
        }

        restoreAuthForm(form, form.dataset.networkError, 'error');
    }
}

async function recoverAuthRequest(form) {
    try {
        const response = await fetch(form.dataset.recoveryUrl, {
            credentials: 'same-origin',
            headers: { Accept: form.dataset.authForm === 'login' ? 'application/json' : 'text/html' },
        });

        if (!response.ok) {
            return false;
        }

        if (form.dataset.authForm === 'login') {
            const recoveryResult = await response.json();
            if (!recoveryResult.authenticated || typeof recoveryResult.redirect !== 'string') {
                return false;
            }

            const loginDestination = new URL(recoveryResult.redirect, window.location.href);
            if (loginDestination.origin !== window.location.origin) {
                return false;
            }

            window.location.assign(loginDestination.href);
            return true;
        }

        if (!response.redirected) {
            const recoverySuccess = form.dataset.recoverySuccessUrl
                ? new URL(form.dataset.recoverySuccessUrl, window.location.href)
                : null;
            const recoveryDestination = new URL(response.url);
            if (
                recoverySuccess
                && recoveryDestination.origin === window.location.origin
                && recoveryDestination.pathname === recoverySuccess.pathname
            ) {
                window.location.assign(recoveryDestination.href);
                return true;
            }

            return false;
        }

        const destination = new URL(response.url);
        const recoveryPage = new URL(form.dataset.recoveryUrl, window.location.href);
        if (destination.origin !== window.location.origin) {
            return false;
        }

        const recoverySuccessPage = form.dataset.recoverySuccessUrl
            ? new URL(form.dataset.recoverySuccessUrl, window.location.href)
            : null;
        const reachedSuccessPage = recoverySuccessPage
            ? destination.pathname === recoverySuccessPage.pathname
            : response.redirected && destination.pathname !== recoveryPage.pathname;

        if (reachedSuccessPage) {
            window.location.assign(destination.href);
            return true;
        }
    } catch {
        return false;
    }

    return false;
}

function extractAuthErrors(html) {
    if (html && typeof html === 'object') {
        const validationErrors = Object.values(html.errors ?? {}).flat()
            .filter(message => typeof message === 'string');
        if (validationErrors.length > 0) {
            return [...new Set(validationErrors)].join(' ');
        }
        if (typeof html.message === 'string') {
            return html.message;
        }
        return '';
    }

    const parsedPage = new DOMParser().parseFromString(html, 'text/html');
    const messages = Array.from(parsedPage.querySelectorAll('.alert-danger, .error-message:not([hidden])'))
        .map(element => element.textContent.replace(/\s+/g, ' ').trim())
        .filter(Boolean);

    return [...new Set(messages)].join(' ');
}

function restoreAuthForm(form, message = '', messageType = '') {
    const button = form.querySelector('[data-auth-submit]');
    const buttonLabel = form.querySelector('[data-auth-button-label]');
    const spinner = form.querySelector('[data-auth-spinner]');
    const requestStatus = form.querySelector('[data-auth-request-status]');
    const termsCheckbox = form.querySelector('#terms_accepted');

    delete form.dataset.submissionLocked;
    form.removeAttribute('aria-busy');
    button?.classList.remove('is-loading');
    button?.removeAttribute('aria-busy');
    if (button) {
        button.disabled = Boolean(termsCheckbox && !termsCheckbox.checked);
    }
    if (buttonLabel) {
        buttonLabel.textContent = buttonLabel.dataset.initialLabel ?? buttonLabel.textContent;
    }
    if (spinner) {
        spinner.hidden = true;
    }
    if (requestStatus) {
        requestStatus.textContent = message;
        requestStatus.hidden = message === '';
        requestStatus.classList.toggle('is-error', messageType === 'error');
        requestStatus.classList.toggle('is-success', messageType === 'success');
    }
}

// Validate individual field
function validateField(field) {
    const value = field.type === 'password' ? field.value : field.value.trim();
    let isValid = false;

    if (field.type === 'email') {
        isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    } else if (field.name === 'password_confirmation') {
        const password = document.querySelector('input[name="password"]');
        isValid = value !== '' && value === password.value;
        field.setCustomValidity(value !== '' && !isValid ? 'Passwords do not match.' : '');
    } else if (field.name === 'password' && field.form?.dataset.authForm === 'register') {
        const length = Array.from(value).length;
        const requirements = [
            [length >= 12, 'The password must be at least 12 characters.'],
            [/\p{Lu}/u.test(value), 'The password must contain at least one uppercase letter.'],
            [/\p{N}/u.test(value), 'The password must contain at least one number.'],
            [/[\p{S}\p{P}]/u.test(value), 'The password must contain at least one symbol.']
        ];
        const failedRequirement = requirements.find(([passes]) => !passes);
        isValid = !failedRequirement;
        field.setCustomValidity(failedRequirement?.[1] ?? '');
    } else if (field.name === 'password') {
        isValid = value.length > 0;
        field.setCustomValidity('');
    } else if (field.name === 'name') {
        isValid = value.length >= 2;
    } else {
        isValid = value.length > 0;
    }

    if (isValid) {
        field.classList.remove('error');
    } else {
        field.classList.add('error');
    }

    return isValid;
}

// Show/hide password functionality
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }
}
/**
 * SPES Portal — public/js/auth.js
 * Place this file at: public/js/auth.js
 * Loaded via: {{ asset('js/auth.js') }}  (at the bottom of welcome.blade.php)
 */

document.addEventListener('DOMContentLoaded', () => {

    /* ------------------------------------------------------------------
       1.  SCROLL-TRIGGERED ANIMATIONS
           Watches .fade-in-up, .fade-in, .slide-in-left, .slide-in-right
           and adds the .visible class when each element enters the viewport.
    ------------------------------------------------------------------ */
    const animatedElements = document.querySelectorAll(
        '.fade-in-up, .fade-in, .slide-in-left, .slide-in-right'
    );

    const observerOptions = {
        threshold: 0.15,              // trigger when 15 % of element is visible
        rootMargin: '0px 0px -50px 0px'  // slightly before the bottom edge
    };

    const appearOnScroll = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;

            entry.target.classList.add('visible');
            observer.unobserve(entry.target); // animate only once
        });
    }, observerOptions);

    animatedElements.forEach(el => appearOnScroll.observe(el));


    /* ------------------------------------------------------------------
       2.  NAVBAR SHADOW ON SCROLL
           Deepens the navbar box-shadow once the user scrolls past 50 px.
    ------------------------------------------------------------------ */
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', () => {
        if (!navbar) return;

        navbar.style.boxShadow = window.scrollY > 50
            ? '0 5px 20px rgba(0, 0, 0, 0.1)'
            : '0 2px 10px rgba(0, 0, 0, 0.05)';
    });


    /* ------------------------------------------------------------------
       3.  HAMBURGER MENU TOGGLE
           Toggles the mobile menu on burger click and closes when a link is clicked.
    ------------------------------------------------------------------ */
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileLinks = mobileMenu ? mobileMenu.querySelectorAll('a') : [];

    // Toggle menu on hamburger click
    if (hamburgerBtn && mobileMenu) {
        hamburgerBtn.addEventListener('click', () => {
            hamburgerBtn.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        });
    }

    // Close menu when a link is clicked
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            hamburgerBtn.classList.remove('active');
            mobileMenu.classList.remove('active');
        });
    });

    const announcementLauncher = document.getElementById('announcement-launcher');
    const announcementPopup = document.getElementById('announcement-popup');
    const announcementCloseButtons = announcementPopup?.querySelectorAll('[data-announcement-close]') ?? [];

    function openAnnouncements() {
        if (!announcementPopup || announcementPopup.open) return;

        announcementPopup.showModal();
        announcementLauncher?.setAttribute('aria-expanded', 'true');
        announcementPopup.querySelector('[data-announcement-close]')?.focus();
    }

    function closeAnnouncements() {
        if (announcementPopup?.open) announcementPopup.close();
    }

    announcementLauncher?.addEventListener('click', openAnnouncements);
    announcementCloseButtons.forEach(button => button.addEventListener('click', closeAnnouncements));
    announcementPopup?.addEventListener('close', () => {
        announcementLauncher?.setAttribute('aria-expanded', 'false');
    });
    announcementPopup?.addEventListener('click', event => {
        if (event.target === announcementPopup) closeAnnouncements();
    });

    if (announcementPopup?.dataset.autoOpen === 'true') openAnnouncements();


    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (hamburgerBtn && mobileMenu && !e.target.closest('.navbar')) {
            hamburgerBtn.classList.remove('active');
            mobileMenu.classList.remove('active');
        }
    });

});

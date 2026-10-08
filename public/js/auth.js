// Auth Form Validation and Effects
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    if (form) {
        const submitBtn = form.querySelector('.btn-submit');
        const termsCheckbox = form.querySelector('#terms_accepted');
        const termsError = document.getElementById('terms-error');

        if (termsCheckbox && submitBtn) {
            submitBtn.disabled = !termsCheckbox.checked;

            termsCheckbox.addEventListener('change', function() {
                submitBtn.disabled = !this.checked;
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

        // Form submission handler
        form.addEventListener('submit', function(e) {
            // Disable submit button to prevent double submission
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
    } else if (field.name === 'password') {
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

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (hamburgerBtn && mobileMenu && !e.target.closest('.navbar')) {
            hamburgerBtn.classList.remove('active');
            mobileMenu.classList.remove('active');
        }
    });

});

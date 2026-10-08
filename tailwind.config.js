import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                portal: {
                    bg: 'var(--theme-bg)',
                    surface: 'var(--theme-surface)',
                    card: 'var(--theme-card)',
                    elevated: 'var(--theme-elevated)',
                    maroon: {
                        DEFAULT: 'var(--theme-maroon)',
                        hover: 'var(--theme-maroon-hover)',
                    },
                    mustard: {
                        DEFAULT: 'var(--theme-mustard)',
                        bright: 'var(--theme-mustard-bright)',
                    },
                    text: {
                        DEFAULT: 'var(--theme-text)',
                        secondary: 'var(--theme-text-secondary)',
                        muted: 'var(--theme-text-muted)',
                    },
                    border: 'var(--theme-border)',
                    success: 'var(--theme-success)',
                    danger: 'var(--theme-danger)',
                },
            },
        },
    },

    plugins: [forms],
};

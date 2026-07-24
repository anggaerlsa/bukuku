import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Colours and fonts are driven by CSS custom properties so a novel's theme can
 * repaint the whole UI by overriding a handful of variables (see app.css).
 * Channels are stored space-separated ("79 70 229") so Tailwind's <alpha-value>
 * keeps working — bg-accent/25 and friends still do what you expect.
 */
const rgb = (name) => `rgb(var(--c-${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Support/NovelTheme.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['var(--font-sans)', ...defaultTheme.fontFamily.sans],
                display: ['var(--font-display)', ...defaultTheme.fontFamily.sans],
                serif: ['var(--font-serif)', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                surface: {
                    DEFAULT: rgb('surface'),
                    muted: rgb('surface-muted'),
                    sunken: rgb('surface-sunken'),
                },
                ink: {
                    DEFAULT: rgb('ink'),
                    light: rgb('ink-light'),
                    faint: rgb('ink-faint'),
                },
                line: {
                    DEFAULT: rgb('line'),
                    strong: rgb('line-strong'),
                },
                accent: {
                    DEFAULT: rgb('accent'),
                    light: rgb('accent-light'),
                    dark: rgb('accent-dark'),
                    soft: rgb('accent-soft'),
                },
                danger: {
                    DEFAULT: rgb('danger'),
                    dark: rgb('danger-dark'),
                },
                success: {
                    DEFAULT: rgb('success'),
                },
                shell: {
                    DEFAULT: rgb('shell'),
                    dark: rgb('shell-dark'),
                },
            },
            boxShadow: {
                card: '0 1px 2px rgb(var(--c-ink) / .06), 0 1px 3px rgb(var(--c-ink) / .08)',
                accent: '0 0 0 1px rgb(var(--c-accent) / .25), 0 8px 24px rgb(var(--c-accent) / .14)',
            },
        },
    },

    plugins: [forms],
};

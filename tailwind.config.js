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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['"Source Serif 4"', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                // Neutral, genre-agnostic surface + text
                surface: {
                    DEFAULT: '#ffffff',
                    muted: '#f4f5f7',
                    sunken: '#eceef2',
                },
                ink: {
                    DEFAULT: '#111827',
                    light: '#6b7280',
                    faint: '#9ca3af',
                },
                line: {
                    DEFAULT: '#e5e7eb',
                    strong: '#d1d5db',
                },
                // Single calm accent
                accent: {
                    DEFAULT: '#4f46e5',
                    light: '#818cf8',
                    dark: '#4338ca',
                    soft: '#eef2ff',
                },
                danger: {
                    DEFAULT: '#dc2626',
                    dark: '#b91c1c',
                },
                success: {
                    DEFAULT: '#059669',
                },
                // Dark chrome (top bar / footer)
                shell: {
                    DEFAULT: '#1f2937',
                    dark: '#111827',
                },
            },
            boxShadow: {
                card: '0 1px 2px rgba(17,24,39,.06), 0 1px 3px rgba(17,24,39,.08)',
                accent: '0 0 0 1px rgba(79,70,229,.25), 0 8px 24px rgba(79,70,229,.14)',
            },
        },
    },

    plugins: [forms],
};

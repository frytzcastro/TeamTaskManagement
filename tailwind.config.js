import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                card: 'var(--radius-card)',
                control: 'var(--radius-control)',
            },
            boxShadow: {
                soft: 'var(--shadow-soft)',
                lift: 'var(--shadow-lift)',
                bar: 'var(--shadow-bar)',
            },
            transitionDuration: {
                fast: '150ms',
                surface: '200ms',
            },
            transitionTimingFunction: {
                out: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            keyframes: {
                'badge-pulse': {
                    '0%, 100%': { transform: 'scale(1)' },
                    '50%': { transform: 'scale(1.12)' },
                },
            },
            animation: {
                'badge-pulse': 'badge-pulse 1.2s cubic-bezier(0.22, 1, 0.36, 1) 1',
            },
            colors: {
                app: 'rgb(var(--color-app) / <alpha-value>)',
                sidebar: 'rgb(var(--color-sidebar) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                brand: {
                    DEFAULT: 'rgb(var(--color-brand) / <alpha-value>)',
                    hover: 'rgb(var(--color-brand-hover) / <alpha-value>)',
                    soft: 'rgb(var(--color-brand-soft) / <alpha-value>)',
                },
                border: {
                    DEFAULT: 'rgb(var(--color-border) / <alpha-value>)',
                },
                ink: {
                    DEFAULT: 'rgb(var(--color-ink) / <alpha-value>)',
                    muted: 'rgb(var(--color-ink-muted) / <alpha-value>)',
                },
                success: {
                    DEFAULT: 'rgb(var(--color-success) / <alpha-value>)',
                    soft: 'rgb(var(--color-success-soft) / <alpha-value>)',
                },
                warning: {
                    DEFAULT: 'rgb(var(--color-warning) / <alpha-value>)',
                    soft: 'rgb(var(--color-warning-soft) / <alpha-value>)',
                },
                danger: {
                    DEFAULT: 'rgb(var(--color-danger) / <alpha-value>)',
                    soft: 'rgb(var(--color-danger-soft) / <alpha-value>)',
                },
                info: {
                    DEFAULT: 'rgb(var(--color-info) / <alpha-value>)',
                    soft: 'rgb(var(--color-info-soft) / <alpha-value>)',
                },
            },
        },
    },

    plugins: [forms],
};

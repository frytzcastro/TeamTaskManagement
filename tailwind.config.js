import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import animate from 'tailwindcss-animate';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,vue}',
    ],

    theme: {
        container: {
            center: true,
            padding: '2rem',
            screens: {
                '2xl': '1400px',
            },
        },
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                card: 'var(--radius-card)',
                control: 'var(--radius-control)',
                lg: 'var(--radius)',
                md: 'calc(var(--radius) - 2px)',
                sm: 'calc(var(--radius) - 4px)',
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
                'accordion-down': {
                    from: { height: '0' },
                    to: { height: 'var(--radix-accordion-content-height)' },
                },
                'accordion-up': {
                    from: { height: 'var(--radix-accordion-content-height)' },
                    to: { height: '0' },
                },
            },
            animation: {
                'badge-pulse': 'badge-pulse 1.2s cubic-bezier(0.22, 1, 0.36, 1) 1',
                'accordion-down': 'accordion-down 0.2s ease-out',
                'accordion-up': 'accordion-up 0.2s ease-out',
            },
            colors: {
                // Existing app tokens (RGB channel vars)
                app: 'rgb(var(--color-app) / <alpha-value>)',
                sidebar: 'rgb(var(--color-sidebar) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                brand: {
                    DEFAULT: 'rgb(var(--color-brand) / <alpha-value>)',
                    hover: 'rgb(var(--color-brand-hover) / <alpha-value>)',
                    soft: 'rgb(var(--color-brand-soft) / <alpha-value>)',
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

                // shadcn-vue semantic tokens (HSL channel vars)
                border: {
                    DEFAULT: 'hsl(var(--border) / <alpha-value>)',
                },
                input: 'hsl(var(--input) / <alpha-value>)',
                ring: 'hsl(var(--ring) / <alpha-value>)',
                background: 'hsl(var(--background) / <alpha-value>)',
                foreground: 'hsl(var(--foreground) / <alpha-value>)',
                primary: {
                    DEFAULT: 'hsl(var(--primary) / <alpha-value>)',
                    foreground: 'hsl(var(--primary-foreground) / <alpha-value>)',
                },
                secondary: {
                    DEFAULT: 'hsl(var(--secondary) / <alpha-value>)',
                    foreground: 'hsl(var(--secondary-foreground) / <alpha-value>)',
                },
                destructive: {
                    DEFAULT: 'hsl(var(--destructive) / <alpha-value>)',
                    foreground: 'hsl(var(--destructive-foreground) / <alpha-value>)',
                },
                muted: {
                    DEFAULT: 'hsl(var(--muted) / <alpha-value>)',
                    foreground: 'hsl(var(--muted-foreground) / <alpha-value>)',
                },
                accent: {
                    DEFAULT: 'hsl(var(--accent) / <alpha-value>)',
                    foreground: 'hsl(var(--accent-foreground) / <alpha-value>)',
                },
                popover: {
                    DEFAULT: 'hsl(var(--popover) / <alpha-value>)',
                    foreground: 'hsl(var(--popover-foreground) / <alpha-value>)',
                },
                card: {
                    DEFAULT: 'hsl(var(--card) / <alpha-value>)',
                    foreground: 'hsl(var(--card-foreground) / <alpha-value>)',
                },
            },
        },
    },

    plugins: [forms, animate],
};

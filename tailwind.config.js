import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['selector', '[data-theme="dark"]'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['var(--nova-font-sans)', ...defaultTheme.fontFamily.sans],
                mono: ['var(--nova-font-mono)', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                primary: {
                    50: 'var(--nova-color-primary-50)',
                    100: 'var(--nova-color-primary-100)',
                    200: 'var(--nova-color-primary-200)',
                    400: 'var(--nova-color-primary-400)',
                    500: 'var(--nova-color-primary-500)',
                    600: 'var(--nova-color-primary-600)',
                    700: 'var(--nova-color-primary-700)',
                    900: 'var(--nova-color-primary-900)',
                    DEFAULT: 'var(--nova-color-primary-600)',
                },
                accent: {
                    500: 'var(--nova-color-accent-500)',
                    600: 'var(--nova-color-accent-600)',
                    DEFAULT: 'var(--nova-color-accent-600)',
                },
                success: {
                    DEFAULT: 'var(--nova-color-success)',
                    soft: 'var(--nova-color-success-soft)',
                },
                warning: {
                    DEFAULT: 'var(--nova-color-warning)',
                    soft: 'var(--nova-color-warning-soft)',
                },
                danger: {
                    DEFAULT: 'var(--nova-color-danger)',
                    soft: 'var(--nova-color-danger-soft)',
                },
                info: {
                    DEFAULT: 'var(--nova-color-info)',
                    soft: 'var(--nova-color-info-soft)',
                },
                surface: {
                    card: 'var(--nova-color-surface-card)',
                    muted: 'var(--nova-color-surface-muted)',
                },
                app: {
                    DEFAULT: 'var(--nova-color-bg-app)',
                    elevated: 'var(--nova-color-bg-elevated)',
                    sunken: 'var(--nova-color-bg-sunken)',
                },
                ink: {
                    DEFAULT: 'var(--nova-color-text)',
                    heading: 'var(--nova-color-text-heading)',
                    muted: 'var(--nova-color-text-muted)',
                },
                line: {
                    DEFAULT: 'var(--nova-color-border)',
                    strong: 'var(--nova-color-border-strong)',
                    focus: 'var(--nova-color-border-focus)',
                },
                sidebar: {
                    DEFAULT: 'var(--nova-color-sidebar-bg)',
                    border: 'var(--nova-color-sidebar-border)',
                    text: 'var(--nova-color-sidebar-text)',
                    muted: 'var(--nova-color-sidebar-text-muted)',
                    active: 'var(--nova-color-sidebar-active)',
                    hover: 'var(--nova-color-sidebar-hover)',
                },
            },
            borderRadius: {
                sm: 'var(--nova-radius-sm)',
                DEFAULT: 'var(--nova-radius-md)',
                md: 'var(--nova-radius-md)',
                lg: 'var(--nova-radius-lg)',
                xl: 'var(--nova-radius-xl)',
                '2xl': 'var(--nova-radius-2xl)',
            },
            boxShadow: {
                xs: 'var(--nova-shadow-xs)',
                sm: 'var(--nova-shadow-sm)',
                md: 'var(--nova-shadow-md)',
                lg: 'var(--nova-shadow-lg)',
                focus: 'var(--nova-shadow-focus)',
            },
            transitionDuration: {
                fast: 'var(--nova-duration-fast)',
                normal: 'var(--nova-duration-normal)',
                moderate: 'var(--nova-duration-moderate)',
                slow: 'var(--nova-duration-slow)',
            },
            zIndex: {
                sticky: 'var(--nova-z-sticky)',
                sidebar: 'var(--nova-z-sidebar)',
                dropdown: 'var(--nova-z-dropdown)',
                drawer: 'var(--nova-z-drawer)',
                modal: 'var(--nova-z-modal)',
                toast: 'var(--nova-z-toast)',
                command: 'var(--nova-z-command)',
                tooltip: 'var(--nova-z-tooltip)',
            },
            width: {
                sidebar: 'var(--nova-sidebar-width)',
                'sidebar-collapsed': 'var(--nova-sidebar-width-collapsed)',
            },
            spacing: {
                'page-x': 'clamp(1rem, 2vw, 2rem)',
                'page-y': 'var(--nova-space-page-y, 1.5rem)',
            },
        },
    },

    plugins: [forms],
};

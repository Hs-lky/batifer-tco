import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                navy: '#16316b',
                'navy-2': '#1e3a5f',
                accent: '#00a887',
                'accent-2': '#00c49f',
                danger: '#d93025',
                warn: '#f59e0b',
                ok: '#0f9d58',
                bg: '#f0f4f9',
                card: '#ffffff',
                border: '#c5d0e0',
                text: '#1a2a3a',
                muted: '#5a7090',
            },
            fontFamily: {
                mono: ['"Space Mono"', ...defaultTheme.fontFamily.mono],
                sans: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Enums/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Palet SekawanFleet (dari desain Google Stitch)
                brand: {
                    50: '#F0FDFA',
                    100: '#CCFBF1',
                    200: '#99F6E4',
                    600: '#0D9488',
                    700: '#0F766E', // primary
                    800: '#115E59', // hover
                    900: '#134E4A', // active
                },
                accent: {
                    500: '#F59E0B',
                    600: '#D97706',
                },
                sidebar: {
                    DEFAULT: '#0F172A',
                    hover: '#1E293B',
                    muted: '#94A3B8',
                },
            },
        },
    },

    plugins: [forms],
};

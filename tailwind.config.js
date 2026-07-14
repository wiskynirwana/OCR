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
            },
            colors: {
                // Palet "Sage / Pine hangat": kertas hangat, aksen cemara teduh.
                paper:   '#F7F5F1',
                surface: '#FFFFFF',
                line:    '#E7E2D9',
                ink: {
                    DEFAULT: '#2B2A26',
                    soft:    '#6B665C',
                    faint:   '#9A9488',
                },
                pine: {
                    DEFAULT: '#40655A',
                    dark:    '#33514A',
                    soft:    '#EAF0EC',
                },
                clay: {
                    DEFAULT: '#B08968',
                    dark:    '#8A5F3D',
                    soft:    '#F4ECE2',
                },
                danger: {
                    DEFAULT: '#B4443A',
                    dark:    '#98362E',
                    soft:    '#F6E9E7',
                },
            },
            boxShadow: {
                card: '0 1px 2px rgba(43, 42, 38, 0.04), 0 1px 3px rgba(43, 42, 38, 0.06)',
                soft: '0 8px 30px rgba(43, 42, 38, 0.08)',
            },
        },
    },

    plugins: [forms],
};

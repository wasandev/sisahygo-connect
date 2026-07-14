import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                'connect-blue': {
                    50: '#eef8ff',
                    100: '#d7efff',
                    200: '#b8e2ff',
                    300: '#88ceff',
                    400: '#50b0f4',
                    500: '#2092df',
                    600: '#0875D1',
                    700: '#075fa8',
                    800: '#084f8b',
                    900: '#0b4273',
                },

                'connect-orange': {
                    50: '#fff7ed',
                    100: '#ffedd5',
                    200: '#fed7aa',
                    300: '#fdba74',
                    400: '#fb923c',
                    500: '#F47A16',
                    600: '#ea580c',
                    700: '#c2410c',
                    800: '#9a3412',
                    900: '#7c2d12',
                },

                'connect-navy': {
                    500: '#315a79',
                    600: '#244964',
                    700: '#193a55',
                    800: '#102f4d',
                    900: '#0B2A4A',
                },
            },
        },
    },

    plugins: [forms],
};

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'corp': {
                    orange: '#FF5501', // Color primario / Acción / Highlight
                    blue: '#00408F',   // Color institucional / Header / Sidebar
                    gray: '#BBBBBB',   // Bordes / Detalles neutros
                    white: '#FFFFFF',  // Blanco puro
                    graydark: '#333333', // Texto oscuro / Detalles
                }
            },
        },
    },

    plugins: [forms, typography],
};

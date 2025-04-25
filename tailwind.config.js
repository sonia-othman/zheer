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
          // Custom fonts
          fontFamily: {
            sans: ['"Noto Sans Arabic"', ...defaultTheme.fontFamily.sans],
            display: ['"Poppins"', 'sans-serif'],
          },
          
          // Custom colors
          colors: {
            primary: {
              light: '#737791',
              DEFAULT: '#2A55A2',
              dark: '#2C2C2C',
            },
            secondary: {
              light: '#f6ad55',
              DEFAULT: '#ed8936',
              dark: '#dd6b20',
            },
          },
        },
    },

    plugins: [require('@tailwindcss/forms'), forms],
};

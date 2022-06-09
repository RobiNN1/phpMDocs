/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './twig/**/*.twig',
        './assets/js/scripts.js'
    ],
    theme: {
        extend: {},
    },
    plugins: [],
    safelist: [
        'table-auto',
        'w-full',
        'max-w-full',
        'h-auto',
        'text-left',
    ],
}

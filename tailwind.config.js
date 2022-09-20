/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.twig',
        './assets/js/scripts.js'
    ],
    theme: {
        extend: {},
    },
    corePlugins: {
        textOpacity: false,
        backgroundOpacity: false,
        borderOpacity: false,
        divideOpacity: false,
        placeholderOpacity: false,
        ringOpacity: false,
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

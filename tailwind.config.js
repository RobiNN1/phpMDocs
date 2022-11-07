/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.twig',
        './assets/js/scripts.js'
    ],
    theme: {
        extend: {},
    },
    safelist: [
        'table-auto',
        'w-full',
        'max-w-full',
        'h-auto',
        'text-left',
    ],
    corePlugins: {
        textOpacity: false,
        backgroundOpacity: false,
        borderOpacity: false,
        divideOpacity: false,
        placeholderOpacity: false,
        ringOpacity: false,
    },
    plugins: [],
    experimental: {
        optimizeUniversalDefaults: true
    }
}

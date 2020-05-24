module.exports = {
    purge: [
        './resources/js/**/*.vue',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            spacing: {
                '72': '18rem',
                '84': '21rem',
                '96': '24rem',
            },
        },
    },
    variants: {},
    plugins: [],
};

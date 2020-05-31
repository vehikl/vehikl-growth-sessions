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
        maxHeight: {
            '32': '8rem',
            '48': '12rem',
            '64': '16rem',
        },
    },
    variants: {},
    plugins: [],
};

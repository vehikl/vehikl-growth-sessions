module.exports = {
    purge: {
        content: [
            './resources/js/**/*.vue',
            './resources/views/**/*.blade.php',
            './resources/svgs/**/*.vue',
        ],
        options: {
            whitelist: ['w-12', 'h-12'],
        }
    },
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

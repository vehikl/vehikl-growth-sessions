module.exports = {
    content: ["*"],
    purge: {
        content: [
            './resources/js/**/*.vue',
            './resources/views/**/*.blade.php',
            './resources/svgs/**/*.vue',
        ],
        options: {
            whitelist: ['h-6', 'w-6', 'w-12', 'h-12'],
        }
    },
    theme: {
        extend: {
            colors: {
                vehikl: {
                    DEFAULT: '#dd5f13',
                    orange: '#dd5f13'
                }
            },
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

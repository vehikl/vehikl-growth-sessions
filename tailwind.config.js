module.exports = {
    content: [
        './resources/js/**/*.vue',
        './resources/views/**/*.blade.php',
        './resources/svgs/**/*.vue',
    ],
    safelist: ['h-6', 'w-6', 'w-12', 'h-12'],
    theme: {
        extend: {
            colors: {
                vehikl: {
                    primary:'#111827',
                    secondary:'#D95D13',
                    DEFAULT: '#D95D13',
                    orange: '#D95D13'
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
    plugins: [],
};

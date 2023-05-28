import {defineConfig} from 'vitest/config'
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/app.css',
            'resources/js/app.ts',
        ]),
        vue()
    ],
    test: {
        environment: 'happy-dom',
        setupFiles: ['./setup-vitest.ts'],
        globals: true
    }
});

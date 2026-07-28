import '../css/app.css';

import { initTheme } from '@/composables/useTheme';
import AppLayout from '@/layouts/AppLayout.vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { configureEcho } from '@laravel/echo-vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';

// Sync the reactive theme state with the class the blade template already applied.
initTheme();

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const page = resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<{ default: DefineComponent }>('./pages/**/*.vue'));

        const module = await page;
        module.default.layout = module.default.layout || AppLayout;

        return module.default;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#e0632a',
    },
});

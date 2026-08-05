import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { route as ziggyRoute } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: async (name) => {
            const module = await resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<{ default: DefineComponent }>('./pages/**/*.vue'));
            return module.default;
        },
        setup({ App, props, plugin }) {
            const app = createSSRApp({ render: () => h(App, props) });

            // Configure Ziggy for SSR...
            const ziggyConfig = {
                ...page.props.ziggy!,
                location: new URL(page.props.ziggy!.location),
            };

            // Create route function...
            const route = ((name: string, params?: any, absolute?: boolean) => ziggyRoute(name, params, absolute, ziggyConfig)) as typeof ziggyRoute;

            // Make route function available globally...
            app.config.globalProperties.route = route;

            // Make route function available globally for SSR...
            if (typeof window === 'undefined') {
                globalThis.route = route;
            }

            app.use(plugin);

            return app;
        },
    }),
);

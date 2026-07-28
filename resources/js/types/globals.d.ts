import type { PageProps } from '@inertiajs/core';
import type { Config, route as routeFn } from 'ziggy-js';
import type { Auth } from './IInertia';

declare global {
    var route: typeof routeFn;

    interface ImportMetaEnv {
        readonly VITE_APP_NAME?: string;
    }
}

declare module '@inertiajs/core' {
    interface PageProps {
        auth: Auth;
        ziggy?: Config & { location: string };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof routeFn;
        $page: { props: PageProps };
    }
}

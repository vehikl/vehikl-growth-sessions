import { mount, VueWrapper } from '@vue/test-utils';
import { Component, defineComponent, h } from 'vue';

/**
 * Stub component for Inertia's Head component.
 *
 * Inertia's real <Head> reads `this.$headManager` in `data()`, which only exists inside a
 * mounted Inertia app, so rendering it in a test throws
 * "Cannot read properties of undefined (reading 'createProvider')".
 *
 * It carries no `name` option, so `@vue/test-utils` stubs cannot match it — the only reliable
 * way to swap it out is to replace the module export, which `setup-vitest.ts` does globally.
 * A spec that declares its own `vi.mock('@inertiajs/vue3', ...)` replaces that global mock
 * wholesale and must therefore re-apply this stub itself.
 */
export const InertiaHeadStub = defineComponent({
    name: 'InertiaHeadStub',
    props: ['title'],
    setup(props, { slots }) {
        return () => h('div', { 'data-testid': 'inertia-head' }, slots.default?.());
    },
});

/**
 * Mounts a Vue component with Inertia.js context for testing.
 *
 * @param component - The Vue component to mount
 * @param options - Standard @vue/test-utils mount options
 * @returns VueWrapper instance
 */
export function mountWithInertia(component: Component, options: Record<string, any> = {}): VueWrapper {
    // Merge global stubs with user-provided options
    const mountOptions = {
        ...options,
        global: {
            ...options.global,
            stubs: {
                Head: InertiaHeadStub,
                ...options.global?.stubs,
            },
        },
    };

    return mount(component, mountOptions);
}

import { mount, VueWrapper } from '@vue/test-utils';
import { Component, defineComponent, h } from 'vue';

/**
 * Stub component for Inertia's Head component.
 * This prevents "Cannot read properties of undefined (reading 'createProvider')" errors
 * that occur when testing components that use <Head> outside of an Inertia app context.
 */
const HeadStub = defineComponent({
    name: 'Head',
    props: ['title'],
    setup(props, { slots }) {
        return () => h('div', { 'data-testid': 'inertia-head' }, slots.default?.());
    }
});

/**
 * Mounts a Vue component with Inertia.js context for testing.
 *
 * This helper provides the necessary stubs for Inertia components (like <Head>)
 * that require Inertia provider context during testing.
 *
 * @param component - The Vue component to mount
 * @param options - Standard @vue/test-utils mount options
 * @returns VueWrapper instance
 */
export function mountWithInertia(
    component: Component,
    options: Record<string, any> = {}
): VueWrapper {
    // Merge global stubs with user-provided options
    const mountOptions = {
        ...options,
        global: {
            ...options.global,
            stubs: {
                Head: HeadStub,
                ...options.global?.stubs,
            },
        },
    };

    return mount(component, mountOptions);
}

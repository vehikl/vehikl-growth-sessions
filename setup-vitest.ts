import {expect, vi} from "vitest"
import "ziggy-js";
import { defineComponent, h } from 'vue';

window.alert = vi.fn()

// Mock Inertia's Head component to prevent provider errors in tests
const HeadStub = defineComponent({
    name: 'Head',
    props: ['title'],
    setup(props, { slots }) {
        return () => h('div', { 'data-testid': 'inertia-head' }, slots.default?.());
    }
});

// Mock the @inertiajs/vue3 module
vi.mock('@inertiajs/vue3', async () => {
    const actual = await vi.importActual('@inertiajs/vue3');
    return {
        ...actual,
        Head: HeadStub,
    };
});

expect.extend({
    toBeVisible: (received, expected) => {
        if (received.attributes("style")?.includes("display: none")) {
            return {
                message: () => "The wrapper is not visible",
                pass: false
            }
        }

        return {
            pass: true,
            message: () => "The wrapper is visible"
        }
    },

    toBeChecked: (received, expected) => {
        if (received.element?.checked) {
            return {
                message: () => "The wrapper checkbox is checked",
                pass: true
            }
        }

        return {
            pass: false,
            message: () => "The wrapper checkbox is not checked"
        }
    }
})

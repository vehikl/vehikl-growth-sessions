import {expect, vi} from "vitest"
import "ziggy-js";

window.alert = vi.fn()

// Swap Inertia's <Head> for a stub, since the real one needs a mounted Inertia app.
// A spec declaring its own vi.mock('@inertiajs/vue3', ...) replaces this and has to re-apply the stub.
vi.mock('@inertiajs/vue3', async () => {
    const actual = await vi.importActual('@inertiajs/vue3');
    const { InertiaHeadStub } = await import('@/test-utils/inertia-test-helper');

    return {
        ...actual,
        Head: InertiaHeadStub,
    };
});

expect.extend({
    toBeVisible: (received) => {
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

    toBeChecked: (received) => {
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

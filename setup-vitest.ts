import {expect, vi} from "vitest"

window.alert = vi.fn()

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

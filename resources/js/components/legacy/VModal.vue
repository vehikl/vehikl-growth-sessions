<script lang="ts" setup>
import {onMounted, ref, watch} from "vue"

const props = withDefaults(defineProps<{
    state: "open" | "closed"
}>(), {state: "closed"})

const dialog = ref()
const emit = defineEmits(["modal-closed"])
function refreshModalVisibility() {
    if (props.state === "open") {
        dialog.value.showModal()
    } else {
        dialog.value.close()
    }
}

watch(() => props.state, () => {
    refreshModalVisibility()
})

onMounted(() => {
    dialog.value.addEventListener("close", () => {
        emit("modal-closed")
    })
    refreshModalVisibility()
})
</script>

<template>
    <dialog ref="dialog" class="w-full max-w-xl mx-auto mt-4 p-0 overflow-visible">
        <slot></slot>
    </dialog>
</template>

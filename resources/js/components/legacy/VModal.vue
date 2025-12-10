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
    <dialog ref="dialog" class="w-full max-w-2xl mx-auto mt-8 p-0 overflow-visible rounded-2xl shadow-2xl border border-neutral-200 backdrop:bg-black/50 backdrop:backdrop-blur-sm">
        <slot></slot>
    </dialog>
</template>

<style scoped>
dialog::backdrop { animation: fadeIn 0.2s ease-out }
@keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }

dialog {
    margin: 2rem auto;
    max-height: calc(100dvh - 4rem);
    overflow-y: auto;
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px) }
    to { opacity: 1; transform: translateY(0) }
}
</style>

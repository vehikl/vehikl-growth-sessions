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
onMounted(() => {

})


</script>

<template>
    <dialog ref="dialog">
        <slot></slot>
    </dialog>
</template>

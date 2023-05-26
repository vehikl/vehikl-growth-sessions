<script lang="ts" setup>
import {onMounted, ref, watch} from "vue"

const props = withDefaults(defineProps<{
    state: "open" | "closed"
}>(), {state: "closed"})

const dialog = ref()

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
    refreshModalVisibility()
})

</script>

<template>
    <dialog ref="dialog">
        <slot></slot>
    </dialog>
</template>

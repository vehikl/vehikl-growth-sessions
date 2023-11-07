<script lang="ts" setup>
import {computed} from "vue"

const props = withDefaults(defineProps<{
    text: string;
    color: "blue" | "orange" | "red";
}>(), {color: "blue"})

const colorClasses = computed(() => {
    const mapping = {
        blue: "border-blue-600 hover:bg-blue-600 focus:bg-blue-700 text-blue-600",
        orange: "border-amber-600 hover:bg-amber-600 focus:bg-amber-700 text-amber-600",
        red: "border-red-600 hover:bg-red-600 focus:bg-red-700 text-red-600"
    }
    return mapping[props.color]
})
</script>

<template>
    <button
        :class="`w-32 ${colorClasses} border-4 bg-white hover:text-white font-bold py-2 px-4 w-full`"
        @click.prevent="() => $emit('click')"
    >
        {{ text }}
    </button>
</template>

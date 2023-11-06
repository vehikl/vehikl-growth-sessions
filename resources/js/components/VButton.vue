<script lang="ts" setup>
import {computed} from "vue"

const props = withDefaults(defineProps<{
    text: string;
    color: "blue" | "orange" | "red";
}>(), {color: "blue"})

const colorClasses = computed(() => {
    const mapping = {
        blue: "bg-blue-500 hover:bg-blue-700 focus:bg-blue-700",
        orange: "bg-orange-500 hover:bg-orange-700 focus:bg-orange-700",
        red: "bg-red-500 hover:bg-red-700 focus:bg-red-700"
    }
    return mapping[props.color]
})
</script>

<template>
    <button
        :class="`w-32 ${colorClasses} text-white font-bold py-2 px-4 rounded`"
        @click.prevent="() => $emit('click')"
    >
        {{ text }}
    </button>
</template>

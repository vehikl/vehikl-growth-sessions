<script lang="ts" setup>
import {computed} from "vue"

const props = withDefaults(defineProps<{
    text: string;
    color: "blue" | "orange" | "red";
}>(), {color: "blue"})

const colorClasses = computed(() => {
    const mapping = {
        blue: "border-gray-600 hover:bg-gray-600 focus:bg-gray-700 text-gray-600",
        orange: "border-orange-600 hover:bg-orange-600 focus:bg-orange-700 text-orange-600",
        red: "border-red-600 hover:bg-red-600 focus:bg-red-700 text-red-600"
    }
    return mapping[props.color]
})
</script>

<template>
    <button
        :class="`${colorClasses} border-4 bg-white hover:text-white font-bold py-2 px-4 w-full`"
        @click.prevent="() => $emit('click')"
    >
        {{ text }}
    </button>
</template>

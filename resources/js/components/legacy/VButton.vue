<script lang="ts" setup>
import {computed} from "vue"

const props = withDefaults(defineProps<{
    text: string;
    color: "blue" | "orange" | "red";
}>(), {color: "blue"})

const colorClasses = computed(() => {
    const mapping = {
        blue: "bg-vehikl-dark hover:bg-vehikl-dark/90 focus:ring-vehikl-dark/50 text-white shadow-sm",
        orange: "bg-vehikl-orange hover:bg-vehikl-orange/90 focus:ring-vehikl-orange/50 text-white shadow-sm",
        red: "bg-red-600 hover:bg-red-700 focus:ring-red-500/50 text-white shadow-sm"
    }
    return mapping[props.color]
})
</script>

<template>
    <button
        :class="`${colorClasses} transition-smooth font-semibold py-2.5 px-4 w-full rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2`"
        @click.prevent="() => $emit('click')"
    >
        {{ text }}
    </button>
</template>

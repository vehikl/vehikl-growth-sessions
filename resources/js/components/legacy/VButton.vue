<script lang="ts" setup>
import { computed } from "vue"

const props = withDefaults(defineProps<{
    text: string;
    color?: "blue" | "orange" | "red";
    variant?: "filled" | "outlined";
}>(), {
    color: "blue",
    variant: "filled"
})

defineEmits(['click'])

const colorClasses = computed(() => {
    const base = {
        blue: {
            filled: "bg-vehikl-dark hover:bg-vehikl-dark/90 focus:ring-vehikl-dark/50 text-white shadow-sm",
            outlined: "border border-vehikl-dark text-vehikl-dark hover:bg-vehikl-dark hover:text-white focus:ring-vehikl-dark/50"
        },
        orange: {
            filled: "bg-vehikl-orange hover:bg-vehikl-orange/90 focus:ring-vehikl-orange/50 text-white shadow-sm",
            outlined: "border border-vehikl-orange text-vehikl-orange hover:bg-vehikl-orange hover:text-white focus:ring-vehikl-orange/50"
        },
        red: {
            filled: "bg-red-600 hover:bg-red-700 focus:ring-red-500/50 text-white shadow-sm",
            outlined: "border border-red-600 text-red-600 hover:bg-red-600 hover:text-white focus:ring-red-500/50"
        }
    }

    return base[props.color][props.variant]
})
</script>

<template>
    <button
        :class="`${colorClasses} transition-smooth font-semibold py-2.5 px-4 w-full rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2`"
        @click.stop.prevent="() => $emit('click')"
    >
        {{ text }}
    </button>
</template>

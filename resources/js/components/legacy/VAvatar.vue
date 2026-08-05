<script lang="ts" setup>
import { computed } from 'vue';

const props = withDefaults(defineProps<{ alt: string; size?: number; src: string }>(), { size: 32 });

const sizeClasses = computed(() => {
    const sizeMap: Record<number, string> = {
        6: 'w-6 h-6',
        7: 'w-7 h-7',
        12: 'w-12 h-12',
        16: 'w-16 h-16',
        32: 'w-32 h-32',
    };
    return sizeMap[props.size] || 'w-32 h-32';
});
</script>

<template>
    <div :class="[sizeClasses, 'relative']">
        <div class="group h-full w-full overflow-hidden rounded-full shadow-inner">
            <img :src="src" :alt="alt" class="visible h-full w-full object-cover object-center group-hover:grayscale" />
        </div>
    </div>
</template>

<script lang="ts" setup>
withDefaults(defineProps<{ alt: string, size?: number, src: string }>(), {size: 30})
</script>

<template>
    <div :class="`w-${size} h-${size} relative`">
        <div class="group w-full h-full rounded-full overflow-hidden shadow-inner">
            <img :src="src" :alt="alt"
                 class="object-cover object-center w-full h-full visible group-hover:grayscale"/>
        </div>
    </div>
</template>

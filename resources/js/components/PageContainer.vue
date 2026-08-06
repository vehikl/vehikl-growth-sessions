<script setup lang="ts">
withDefaults(defineProps<{ fullWidth?: boolean }>(), {
    fullWidth: false,
});
</script>

<template>
    <main class="gs-page flex min-h-screen flex-col">
        <div :class="fullWidth ? 'flex flex-1 flex-col px-4 py-6 sm:px-7 sm:py-8' : 'mx-auto w-full max-w-6xl px-6 py-16 sm:px-8 sm:py-20'">
            <slot />
        </div>
    </main>
</template>

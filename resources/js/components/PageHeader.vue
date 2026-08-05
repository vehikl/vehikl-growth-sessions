<script setup lang="ts">
defineProps<{
    eyebrow?: string;
    badge?: string;
    title: string;
    lead?: string;
}>();
</script>

<template>
    <header class="mb-10">
        <div v-if="eyebrow || badge" class="mb-5 flex items-center gap-3">
            <span v-if="eyebrow" class="gs-accent-text text-xs font-semibold tracking-[0.18em] uppercase">{{ eyebrow }}</span>
            <span
                v-if="badge"
                class="gs-accent-text border-vehikl-orange/40 inline-block -rotate-3 rounded-full border px-2.5 py-0.5 text-[0.65rem] font-semibold tracking-[0.14em] uppercase"
            >
                {{ badge }}
            </span>
        </div>

        <h1 class="font-display gs-text-strong text-4xl font-bold tracking-tight sm:text-5xl">{{ title }}</h1>

        <p v-if="$slots.lead || lead" class="gs-text-sub mt-6 max-w-2xl text-lg leading-relaxed">
            <slot name="lead">{{ lead }}</slot>
        </p>
    </header>
</template>

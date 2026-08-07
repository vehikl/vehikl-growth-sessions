<script lang="ts" setup>
import type { IHostedSessionSort } from '@/types';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps<{ sort: IHostedSessionSort }>();

const options: { value: IHostedSessionSort; label: string }[] = [
    { value: 'date', label: 'Date' },
    { value: 'name', label: 'Name' },
    { value: 'attendees', label: 'Attendees' },
];

const visitOptions = { only: ['hosted_sessions', 'sort'], preserveScroll: true, preserveState: true };

const isActive = (option: IHostedSessionSort) => props.sort === option;
</script>

<template>
    <nav class="gs-seg flex flex-none items-center gap-1 rounded-lg p-1" aria-label="Sort sessions">
        <Link
            v-for="option in options"
            :key="option.value"
            v-bind="visitOptions"
            :data-testid="`sort-${option.value}`"
            :href="route('dashboard', { sort: option.value })"
            :aria-current="isActive(option.value) ? 'true' : undefined"
            class="transition-smooth cursor-pointer rounded-md px-3 py-1.5 text-xs font-bold tracking-[0.06em] uppercase"
            :class="isActive(option.value) ? 'gs-text-input' : 'gs-text-muted hover:opacity-70'"
        >
            {{ option.label }}
        </Link>
    </nav>
</template>

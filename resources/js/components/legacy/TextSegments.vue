<script lang="ts" setup>
import type { ITextSegment } from '@/types';
import { ref } from 'vue';

defineProps<{ segments: ITextSegment[] }>();

const brokenImageIndexes = ref(new Set<number>());
const isBrowsableProtocol = (value: string) => /^https?:\/\//i.test(value);
</script>

<template>
    <span class="min-w-0 wrap-anywhere">
        <template v-for="(segment, index) in segments" :key="index">
            <img
                v-if="segment.type === 'image' && !brokenImageIndexes.has(index)"
                :src="segment.value"
                alt="Shared image"
                referrerpolicy="no-referrer"
                loading="lazy"
                class="my-1 block max-h-64 max-w-full rounded-md"
                @error="brokenImageIndexes.add(index)"
            />
            <a
                v-else-if="segment.type === 'link'"
                :href="segment.value"
                class="gs-accent-text pointer-events-auto font-medium break-all"
                v-bind="isBrowsableProtocol(segment.value) ? { target: '_blank', rel: 'noopener noreferrer' } : {}"
                @click.stop
                >{{ segment.value }}</a
            >
            <template v-else>{{ segment.value }}</template>
        </template>
    </span>
</template>

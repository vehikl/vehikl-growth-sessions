<script lang="ts" setup>
import type { ITextSegment } from '@/types';
import { ref, watch } from 'vue';

const props = defineProps<{ segments: ITextSegment[] }>();

const brokenImageIndexes = ref(new Set<number>());
// Segments are a fresh array every time the underlying topic/location/comment changes, so clearing
// on reference change resets stale broken-image state without affecting a single unchanged render
// (e.g. a repeated image url within the same segments array, which must fail independently by index).
watch(
    () => props.segments,
    () => brokenImageIndexes.value.clear(),
);
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

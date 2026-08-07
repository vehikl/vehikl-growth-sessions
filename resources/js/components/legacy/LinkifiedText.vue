<script lang="ts" setup>
import { toTextSegments } from '@/lib/linkify';
import { computed } from 'vue';

const props = defineProps<{
    text: string;
}>();

const segments = computed(() => toTextSegments(props.text));
</script>

<template>
    <span class="min-w-0 wrap-anywhere">
        <template v-for="(segment, index) in segments" :key="index">
            <a
                v-if="segment.type === 'link'"
                :href="segment.value"
                class="gs-accent-text pointer-events-auto font-medium break-all"
                target="_blank"
                rel="noopener noreferrer"
                @click.stop
                >{{ segment.value }}</a
            >
            <template v-else>{{ segment.value }}</template>
        </template>
    </span>
</template>

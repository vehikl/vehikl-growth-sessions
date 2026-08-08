<script lang="ts" setup>
import TextSegments from '@/components/legacy/TextSegments.vue';
import type { ITextSegment } from '@/types';

defineProps<{
    segments: ITextSegment[];
}>();
</script>

<template>
    <TextSegments :segments="segments" />
</template>

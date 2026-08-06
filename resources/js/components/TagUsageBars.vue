<script lang="ts" setup>
import type { ITagUsage } from '@/types';
import { computed } from 'vue';

const props = defineProps<{ tags: ITagUsage[] }>();

const busiestCount = computed(() => Math.max(1, ...props.tags.map((tag) => tag.sessions_count)));

function share(sessionsCount: number): string {
    return `${(sessionsCount / busiestCount.value) * 100}%`;
}
</script>

<template>
    <div v-if="tags.length" class="flex flex-col gap-4">
        <div v-for="tag in tags" :key="tag.id" data-testid="tag-usage" class="flex items-center gap-4">
            <span class="gs-text-muted w-32 flex-none truncate text-xs font-semibold tracking-[0.06em] uppercase">
                {{ tag.name }}
            </span>
            <div class="gs-secondary-bg relative h-2.5 flex-1 overflow-hidden rounded-full">
                <div class="gs-accent-bg absolute inset-y-0 left-0 rounded-full" :style="{ width: share(tag.sessions_count) }"></div>
            </div>
            <span class="gs-text-strong w-6 flex-none text-right text-sm font-bold tabular-nums">
                {{ tag.sessions_count }}
                <span class="sr-only">sessions</span>
            </span>
        </div>
    </div>

    <slot v-else name="empty" />
</template>

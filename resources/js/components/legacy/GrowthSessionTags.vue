<script setup lang="ts">
import { ITag } from '@/types';

interface IProps {
    tags: ITag[];
    selectedTagIds?: number[];
}

const emit = defineEmits(['tagClick']);

function buttonClick(id: number) {
    emit('tagClick', id);
}

defineProps<IProps>();
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <div
            v-for="(tag, i) in tags"
            :id="tag.name"
            :key="`tag.name.${i}`"
            class="tag transition-smooth cursor-pointer rounded-full border px-3.5 py-2 text-xs font-semibold tracking-[0.05em] uppercase"
            :class="
                (selectedTagIds ?? []).includes(tag.id) ? 'gs-accent-bg border-transparent text-white' : 'gs-border gs-text-sub hover:text-gs-accent'
            "
            @click="buttonClick(tag.id)"
        >
            {{ tag.name }}
        </div>
    </div>
</template>

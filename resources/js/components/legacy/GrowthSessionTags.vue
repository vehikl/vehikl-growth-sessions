<script setup lang="ts">
import {ITag} from "@/types";

interface IProps {
    tags: ITag[]
    selectedTagIds?: number[]
}

const emit = defineEmits(['tagClick'])

function buttonClick(id: number) {
    emit('tagClick', id)
}

const props = defineProps<IProps>()
</script>

<template>
    <div class="flex gap-x-3 gap-y-1 flex-wrap h-min">
        <div
            v-for="tag in tags"
            :id="tag.name"
            @click="buttonClick(tag.id)"
            class="tag text-xs inline-flex items-center font-bold leading-sm uppercase px-3 py-1 bg-white border-gray-600 border-4 text-gray-600 rounded-md"
            :class="{
                '!bg-gray-600 text-white': (selectedTagIds ?? []).includes(tag.id),
                'hover:bg-gray-600 hover:text-white cursor-pointer': !!selectedTagIds
            }"
            >
            {{tag.name}}
        </div>
    </div>
</template>

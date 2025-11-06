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
    <div class="flex gap-2 h-min">
        <div
            v-for="tag in tags"
            :id="tag.name"
            @click="buttonClick(tag.id)"
            class="tag text-xs inline-flex items-center font-semibold leading-sm uppercase px-3 py-1.5 bg-white border border-neutral-300 text-neutral-700 rounded-lg shadow-sm transition-smooth"
            :class="{
                '!bg-vehikl-orange !border-vehikl-orange text-white shadow-md': (selectedTagIds ?? []).includes(tag.id),
                'hover:bg-vehikl-orange/10 hover:border-vehikl-orange/50 hover:text-vehikl-orange cursor-pointer': !!selectedTagIds
            }"
            >
            {{tag.name}}
        </div>
    </div>
</template>

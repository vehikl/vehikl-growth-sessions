<script setup lang="ts">
import {GrowthSession} from "../classes/GrowthSession";
import {ITag} from "../types";

interface IProps {
    tags: ITag[]
}

const emit = defineEmits(['tagClick'])

function buttonClick(id: number) {
    emit('tagClick', id)
}


const props = defineProps<IProps>()
</script>

<template>
    <div class="flex gap-x-3 gap-y-1 flex-wrap mb-4">
        <div
            v-for="tag in tags"
            @click="buttonClick(tag.id)"
            class="tag text-xs inline-flex items-center font-bold leading-sm uppercase px-3 py-1 bg-gray-600 text-white rounded-md">
            {{tag.name}}
        </div>
    </div>
</template>

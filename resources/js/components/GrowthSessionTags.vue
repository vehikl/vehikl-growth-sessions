<script setup lang="ts">
import {GrowthSession} from "../classes/GrowthSession";

interface IProps {
    growthSession: GrowthSession
}

const props = defineProps<IProps>()
</script>

<template>
    <div class="flex gap-x-3 gap-y-1 flex-wrap mb-4">
        <div
            v-for="tag in growthSession.tags"
            class="tag text-xs inline-flex items-center font-bold leading-sm uppercase px-3 py-1 bg-blue-200 text-blue-700 rounded-full">
            {{tag.name}}
        </div>
    </div>
</template>

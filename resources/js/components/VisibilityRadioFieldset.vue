<script lang="ts" setup>
import {computed} from "vue"

const props = defineProps<{ modelValue: "all" | "private" | "public" }>()
const emit = defineEmits(["update:modelValue"])
const visibility = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value)
})
</script>

<template>
    <fieldset class="flex gap-5">
        <label class="flex items-center text-blue-800 tracking-wider">
            All
            <input v-model="visibility" name="filter-sessions" type="radio" value="all" class="w-6 h-6 ml-2 accent-blue-800">
        </label>
        <label class="flex items-center text-orange-600 tracking-wider">
            Private Only
            <input id="private" v-model="visibility" name="filter-sessions" type="radio" value="private" class="w-6 h-6 ml-2 accent-orange-600">
        </label>
        <label class="flex items-center text-blue-800 tracking-wider">
            Public Only
            <input id="public" v-model="visibility" name="filter-sessions" type="radio" value="public" class="w-6 h-6 ml-2 accent-blue-800">
        </label>
    </fieldset>
</template>

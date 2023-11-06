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
    <fieldset class="flex justify-end gap-5 mx-4 px-4 py-2 rounded-b-xl text-xl tracking-wide text-sky-800 bg-slate-200">
        <label class="flex items-center">
            All
            <input v-model="visibility" name="filter-sessions" type="radio" value="all" class="w-6 h-6 ml-2 accent-sky-800">
        </label>
        <label class="flex items-center ">
            Private Only
            <input id="private" v-model="visibility" name="filter-sessions" type="radio" value="private" class="w-6 h-6 ml-2 accent-amber-600">
        </label>
        <label class="flex items-center">
            Public Only
            <input id="public" v-model="visibility" name="filter-sessions" type="radio" value="public" class="w-6 h-6 ml-2 accent-sky-800">
        </label>
    </fieldset>
</template>

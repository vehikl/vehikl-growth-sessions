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
    <fieldset class="border flex p-4 rounded border-blue-300 gap-5">
        <label>
            All
            <input v-model="visibility" name="filter-sessions" type="radio" value="all">
        </label>
        <label>
            Private Only
            <input id="private" v-model="visibility" name="filter-sessions" type="radio" value="private">
        </label>
        <label>
            Public Only
            <input id="public" v-model="visibility" name="filter-sessions" type="radio" value="public">
        </label>
    </fieldset>
</template>

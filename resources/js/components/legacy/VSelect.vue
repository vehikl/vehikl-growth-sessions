<script lang="ts" setup>
import {computed} from "vue"
import {IDropdownOption} from "@/types/IDropdownOption"

const props = defineProps<{ modelValue: string | number | null, options: IDropdownOption[] }>()
const emit = defineEmits(["update:modelValue"])
const selectModel = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value)
})
</script>

<template>
    <select v-model="selectModel" class="border py-1 px-2">
        <option disabled selected value=""></option>
        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
</template>

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
    <select v-model="selectModel" class="rounded-lg border border-neutral-300 px-3 py-2 bg-white text-vehikl-dark focus:outline-none focus:ring-2 focus:ring-vehikl-orange/50 focus:border-vehikl-orange transition-smooth cursor-pointer">
        <option disabled selected value="">Select an option</option>
        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
</template>

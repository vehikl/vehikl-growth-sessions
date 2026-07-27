<script lang="ts" setup>
import { IDropdownOption } from '@/types/IDropdownOption';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ modelValue: string | number | null; options: IDropdownOption[]; placeholder?: string }>(), {
    placeholder: 'Select an option',
});
const emit = defineEmits(['update:modelValue']);
const selectModel = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});
</script>

<template>
    <select
        v-model="selectModel"
        class="text-vehikl-dark focus:ring-vehikl-orange/50 focus:border-vehikl-orange transition-smooth cursor-pointer rounded-lg border border-neutral-300 bg-white px-3 py-2 focus:ring-2 focus:outline-none"
    >
        <option disabled selected value="">{{ placeholder }}</option>
        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
</template>

<script lang="ts" setup>
import { IDropdownOption } from '@/types/IDropdownOption';
import { ChevronDown } from 'lucide-vue-next';
import { computed, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });
const props = withDefaults(
    defineProps<{ modelValue: string | number | null; options: IDropdownOption[]; placeholder?: string; clearable?: boolean }>(),
    {
        placeholder: 'Select an option',
        clearable: false,
    },
);
const emit = defineEmits(['update:modelValue']);
const attrs = useAttrs();
const selectModel = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});
</script>

<template>
    <div class="relative">
        <select v-bind="attrs" v-model="selectModel" class="gs-input w-full appearance-none rounded-lg py-2.5 pr-10 pl-3 text-sm">
            <option value="" :disabled="!clearable">{{ placeholder }}</option>
            <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
        <ChevronDown
            aria-hidden="true"
            :size="16"
            :stroke-width="2"
            class="gs-text-muted pointer-events-none absolute top-1/2 right-3 -translate-y-1/2"
        />
    </div>
</template>

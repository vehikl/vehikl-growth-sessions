<script lang="ts" setup>
import { computed } from 'vue';

const props = defineProps<{ modelValue: 'all' | 'private' | 'public' }>();
const emit = defineEmits(['update:modelValue']);
const visibility = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const options: { id: string; value: 'all' | 'private' | 'public'; label: string }[] = [
    { id: 'all', value: 'all', label: 'All' },
    { id: 'private', value: 'private', label: 'Private' },
    { id: 'public', value: 'public', label: 'Public' },
];
</script>

<template>
    <fieldset class="gs-seg flex flex-none rounded-lg p-[3px]">
        <label
            v-for="option in options"
            :key="option.id"
            class="transition-smooth cursor-pointer rounded-md px-4 py-2 text-sm font-semibold whitespace-nowrap"
            :class="visibility === option.value ? 'gs-header-bg text-white' : 'gs-text-sub'"
        >
            {{ option.label }}
            <input :id="option.id" v-model="visibility" name="filter-sessions" type="radio" :value="option.value" class="sr-only" />
        </label>
    </fieldset>
</template>

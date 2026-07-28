<script lang="ts" setup>
import { ITimePreference } from '@/types';
import { ref, watch } from 'vue';
import VSelect from './VSelect.vue';

interface Props {
    modelValue: Omit<ITimePreference, 'id' | 'growth_session_proposal_id' | 'created_at' | 'updated_at'>[];
}

const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue']);

const weekdayOptions = [
    { label: 'Monday', value: 'Monday' },
    { label: 'Tuesday', value: 'Tuesday' },
    { label: 'Wednesday', value: 'Wednesday' },
    { label: 'Thursday', value: 'Thursday' },
    { label: 'Friday', value: 'Friday' },
];

const localPreferences = ref<Omit<ITimePreference, 'id' | 'growth_session_proposal_id' | 'created_at' | 'updated_at'>[]>([...props.modelValue]);

watch(
    localPreferences,
    (newValue) => {
        emit('update:modelValue', newValue);
    },
    { deep: true },
);

function addPreference() {
    localPreferences.value.push({
        weekday: 'Monday',
        start_time: '14:00',
        end_time: '17:00',
    });
}

function removePreference(index: number) {
    localPreferences.value.splice(index, 1);
}
</script>

<template>
    <div class="time-preferences">
        <div v-for="(preference, index) in localPreferences" :key="index" class="mb-4 flex items-center gap-4 rounded bg-slate-100 p-4">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-bold tracking-wide text-slate-700 uppercase">
                    Weekday
                    <v-select v-model="preference.weekday" :options="weekdayOptions" class="mt-1" />
                </label>
            </div>

            <div class="flex-1">
                <label class="mb-1 block text-sm font-bold tracking-wide text-slate-700 uppercase">
                    Start Time
                    <input v-model="preference.start_time" type="time" class="mt-1 block w-full border border-slate-400 p-2" />
                </label>
            </div>

            <div class="flex-1">
                <label class="mb-1 block text-sm font-bold tracking-wide text-slate-700 uppercase">
                    End Time
                    <input v-model="preference.end_time" type="time" class="mt-1 block w-full border border-slate-400 p-2" />
                </label>
            </div>

            <button
                type="button"
                @click="removePreference(index)"
                class="mt-6 cursor-pointer rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700"
            >
                Remove
            </button>
        </div>

        <button
            type="button"
            @click="addPreference"
            class="w-full cursor-pointer rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700"
        >
            Add Time Preference
        </button>
    </div>
</template>

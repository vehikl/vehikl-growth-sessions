<script lang="ts" setup>
import { ITimePreference } from '@/types';
import { ref, watch } from 'vue';
import TimePicker from './TimePicker.vue';
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

watch(localPreferences, (newValue) => {
    emit('update:modelValue', newValue);
}, { deep: true });

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
        <div
            v-for="(preference, index) in localPreferences"
            :key="index"
            class="flex items-center gap-4 mb-4 p-4 bg-slate-100 rounded"
        >
            <div class="flex-1">
                <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-1">
                    Weekday
                    <v-select
                        v-model="preference.weekday"
                        :options="weekdayOptions"
                        class="mt-1"
                    />
                </label>
            </div>

            <div class="flex-1">
                <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-1">
                    Start Time
                    <input
                        v-model="preference.start_time"
                        type="time"
                        class="block w-full border border-slate-400 p-2 mt-1"
                    />
                </label>
            </div>

            <div class="flex-1">
                <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-1">
                    End Time
                    <input
                        v-model="preference.end_time"
                        type="time"
                        class="block w-full border border-slate-400 p-2 mt-1"
                    />
                </label>
            </div>

            <button
                type="button"
                @click="removePreference(index)"
                class="mt-6 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
            >
                Remove
            </button>
        </div>

        <button
            type="button"
            @click="addPreference"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full"
        >
            Add Time Preference
        </button>
    </div>
</template>

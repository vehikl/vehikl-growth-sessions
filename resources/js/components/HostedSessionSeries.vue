<script lang="ts" setup>
import SeriesField from '@/components/legacy/SeriesField.vue';
import { SeriesApi } from '@/services/SeriesApi';
import { nextTick, ref } from 'vue';

const props = defineProps<{ sessionId: number; sessionTitle: string; seriesName: string | null }>();
const emit = defineEmits<{ filed: [] }>();

const isEditing = ref(false);
const isSaving = ref(false);
const failed = ref(false);
const draft = ref('');
const seriesInUse = ref<string[]>([]);
const field = ref<{ focus: () => void } | null>(null);

/** Suggestions are fetched each time the field opens, since filing another row may have created or removed a series. */
async function edit() {
    draft.value = props.seriesName ?? '';
    failed.value = false;
    isEditing.value = true;

    await nextTick();
    field.value?.focus();

    try {
        seriesInUse.value = await SeriesApi.index();
    } catch {
        /* the field still takes anything typed into it */
    }
}

function cancel() {
    isEditing.value = false;
}

/** A blank field takes the session out of its series. */
async function save() {
    isSaving.value = true;
    failed.value = false;

    try {
        await SeriesApi.file(props.sessionId, draft.value.trim() || null);
        isEditing.value = false;
        emit('filed');
    } catch {
        failed.value = true;
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <div class="series-filer">
        <form v-if="isEditing" class="flex flex-wrap items-center gap-2" @submit.prevent="save">
            <label class="sr-only" :for="`series-${sessionId}`">Series</label>
            <SeriesField
                :id="`series-${sessionId}`"
                ref="field"
                v-model="draft"
                :options="seriesInUse"
                class="min-w-56 flex-1"
                placeholder="Series name"
                @keydown.esc="cancel"
            />
            <button
                type="submit"
                data-testid="save-series"
                class="gs-btn-primary cursor-pointer rounded-lg px-3 py-1 text-xs font-bold"
                :disabled="isSaving"
            >
                Save
            </button>
            <button type="button" data-testid="cancel-series" class="gs-text-muted cursor-pointer px-1 text-xs font-semibold" @click="cancel">
                Cancel
            </button>
            <span v-if="failed" role="alert" class="text-gs-finished text-xs font-semibold">Could not save. Try again.</span>
        </form>

        <button
            v-else
            type="button"
            data-testid="edit-series"
            class="gs-text-muted transition-smooth hover:text-gs-accent cursor-pointer text-sm"
            :aria-label="`${seriesName ? 'Change series for' : 'Add to a series:'} ${sessionTitle}`"
            @click="edit"
        >
            <span v-if="seriesName" class="gs-accent-text font-semibold">{{ seriesName }}</span>
            <span v-else>+ Add to a series</span>
        </button>
    </div>
</template>

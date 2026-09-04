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

/**
 * The threads this member is running, offered as suggestions. Fetched each time the field opens
 * rather than once for the page: filing a session is how a thread comes into being - and how the
 * last one ends - so a list gathered before the row above was filed would be missing the very
 * series this row is being joined to.
 */
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

/** A blank field takes the session out of its series, which is how a row is un-filed. */
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

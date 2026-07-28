<script lang="ts" setup>
import { GrowthSessionProposalApi } from '@/services/GrowthSessionProposalApi';
import { TagsApi } from '@/services/TagsApi';
import { IGrowthSessionProposal, IStoreGrowthSessionProposalRequest, ITimePreference, IUser, IValidationError } from '@/types';
import Multiselect from '@vueform/multiselect';
import { computed, onBeforeMount, ref } from 'vue';
import TimePreferenceInput from './TimePreferenceInput.vue';

interface IProps {
    user: IUser;
    proposal?: IGrowthSessionProposal;
}

const props = withDefaults(defineProps<IProps>(), {});
const emit = defineEmits(['submitted']);

const title = ref<string>('');
const topic = ref<string>('');
const tagIds = ref<string[]>([]);
const tagOptions = ref<any>({});
const validationErrors = ref<IValidationError | null>(null);
const timePreferences = ref<Omit<ITimePreference, 'id' | 'growth_session_proposal_id' | 'created_at' | 'updated_at'>[]>([
    {
        weekday: 'Monday',
        start_time: '14:00',
        end_time: '17:00',
    },
]);

const isCreating = computed(() => !props.proposal?.id);
const isReadyToSubmit = computed(() => !!title.value && !!topic.value && timePreferences.value.length > 0);

const storeOrUpdatePayload = computed<IStoreGrowthSessionProposalRequest>(() => ({
    title: title.value,
    topic: topic.value,
    tags: tagIds.value.map((tag) => +tag),
    time_preferences: timePreferences.value,
}));

onBeforeMount(() => {
    getTags();

    if (props.proposal) {
        title.value = props.proposal.title;
        topic.value = props.proposal.topic;
        tagIds.value = props.proposal.tags.map((tag) => tag.id.toString());
        timePreferences.value = props.proposal.time_preferences.map((pref) => ({
            weekday: pref.weekday,
            start_time: pref.start_time,
            end_time: pref.end_time,
        }));
    }
});

function onSubmit() {
    if (isCreating.value) {
        return createProposal();
    }
    updateProposal();
}

function onRequestFailed(exception: any) {
    if (exception.response?.status === 422) {
        validationErrors.value = exception.response.data;
    } else {
        alert('Something went wrong :(');
    }
}

function getError(field: string): string {
    const errors = validationErrors.value?.errors[field];
    return errors ? errors[0] : '';
}

async function createProposal() {
    try {
        const payload = storeOrUpdatePayload.value;
        const proposal: IGrowthSessionProposal = await GrowthSessionProposalApi.store(payload);
        emit('submitted', proposal);
    } catch (e) {
        onRequestFailed(e);
    }
}

async function updateProposal() {
    if (!props.proposal) {
        return;
    }

    try {
        const proposal: IGrowthSessionProposal = await GrowthSessionProposalApi.update(props.proposal.id, storeOrUpdatePayload.value);
        emit('submitted', proposal);
    } catch (e) {
        onRequestFailed(e);
    }
}

async function getTags() {
    try {
        const tagsFromApi = await TagsApi.index();
        tagOptions.value = tagsFromApi.map((tag) => {
            return {
                label: tag.name,
                value: tag.id.toString(),
            };
        });
    } catch (e) {
        onRequestFailed(e);
    }
}
</script>

<template>
    <form @submit.prevent class="proposal-form max-h-[95vh] w-full overflow-y-auto bg-white p-4 pt-10 text-left">
        <label class="mb-6 block text-sm font-bold tracking-wide text-slate-700 uppercase">
            Title
            <input
                id="title"
                v-model="title"
                :class="{ 'error-outline': getError('title') }"
                class="focus:shadow-outline mt-1 block w-full appearance-none border border-slate-400 px-3 py-2 text-lg leading-tight font-normal text-slate-700 shadow focus:outline-none"
                maxlength="255"
                placeholder="In a short sentence, what is this proposal about?"
                type="text"
            />
        </label>

        <div class="mb-4">
            <label class="mb-1 block text-sm font-bold tracking-wide text-slate-700 uppercase" for="topic"> Topic </label>
            <textarea
                id="topic"
                v-model="topic"
                :class="{ 'error-outline': getError('topic') }"
                class="focus:shadow-outline w-full appearance-none border border-slate-400 px-3 py-2 leading-tight text-slate-700 shadow focus:outline-none"
                placeholder="Provide more details about this growth session proposal"
                rows="4"
            />
        </div>

        <label class="mb-6 flex flex-col gap-1 text-sm font-bold tracking-wide text-slate-700 uppercase">
            Tags
            <Multiselect
                v-model="tagIds"
                mode="tags"
                :close-on-select="false"
                :searchable="true"
                :options="tagOptions"
                :classes="{
                    tag: 'bg-slate-100 text-slate-700 text-sm font-semibold py-0.5 pl-2 rounded mr-1 mb-1 flex items-center whitespace-nowrap min-w-0 rtl:pl-0 rtl:pr-2 rtl:mr-0 rtl:ml-1',
                }"
            />
        </label>

        <div class="mb-6">
            <label class="mb-2 block text-sm font-bold tracking-wide text-slate-700 uppercase"> Preferred Time Windows </label>
            <p class="mb-4 text-sm text-slate-600">Add one or more time windows when you'd be available for this growth session.</p>
            <TimePreferenceInput v-model="timePreferences" />
        </div>

        <button
            :class="{ 'cursor-pointer': isReadyToSubmit, 'cursor-not-allowed opacity-25': !isReadyToSubmit }"
            :disabled="!isReadyToSubmit"
            @click="onSubmit"
            class="w-full border-4 border-gray-600 bg-white px-4 py-2 font-bold text-gray-600 hover:bg-gray-600 hover:text-white focus:bg-gray-700"
            type="submit"
            ref="submit-button"
            v-text="isCreating ? 'Create Proposal' : 'Update Proposal'"
        />
    </form>
</template>

<style src="@vueform/multiselect/themes/default.css"></style>

<style lang="scss" scoped>
.error-outline {
    outline: red solid 2px;
}
</style>

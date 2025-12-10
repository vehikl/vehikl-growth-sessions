<script lang="ts" setup>
import { IGrowthSessionProposal, IStoreGrowthSessionProposalRequest, IUser, IValidationError, ITimePreference } from '@/types';
import { GrowthSessionProposalApi } from '@/services/GrowthSessionProposalApi';
import { TagsApi } from '@/services/TagsApi';
import { computed, onBeforeMount, ref } from 'vue';
import Multiselect from '@vueform/multiselect';
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
    let errors = validationErrors.value?.errors[field];
    return errors ? errors[0] : '';
}

async function createProposal() {
    try {
        const payload = storeOrUpdatePayload.value;
        let proposal: IGrowthSessionProposal = await GrowthSessionProposalApi.store(payload);
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
        let proposal: IGrowthSessionProposal = await GrowthSessionProposalApi.update(props.proposal.id, storeOrUpdatePayload.value);
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
    <form @submit.prevent class="proposal-form bg-white w-full p-4 pt-10 text-left overflow-y-auto max-h-[95vh]">
        <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-6">
            Title
            <input
                id="title"
                v-model="title"
                :class="{ 'error-outline': getError('title') }"
                class="shadow block appearance-none border border-slate-400 w-full mt-1 py-2 px-3 text-lg font-normal text-slate-700 leading-tight focus:outline-none focus:shadow-outline"
                maxlength="255"
                placeholder="In a short sentence, what is this proposal about?"
                type="text"
            />
        </label>

        <div class="mb-4">
            <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-1" for="topic">
                Topic
            </label>
            <textarea
                id="topic"
                v-model="topic"
                :class="{ 'error-outline': getError('topic') }"
                class="shadow appearance-none border border-slate-400 w-full py-2 px-3 text-slate-700 leading-tight focus:outline-none focus:shadow-outline"
                placeholder="Provide more details about this growth session proposal"
                rows="4"
            />
        </div>

        <label class="flex flex-col text-slate-700 text-sm uppercase tracking-wide font-bold gap-1 mb-6">
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
            <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-2">
                Preferred Time Windows
            </label>
            <p class="text-sm text-slate-600 mb-4">
                Add one or more time windows when you'd be available for this growth session.
            </p>
            <TimePreferenceInput v-model="timePreferences" />
        </div>

        <button
            :class="{ 'opacity-25 cursor-not-allowed': !isReadyToSubmit }"
            :disabled="!isReadyToSubmit"
            @click="onSubmit"
            class="border-gray-600 hover:bg-gray-600 focus:bg-gray-700 text-gray-600 border-4 bg-white hover:text-white font-bold py-2 px-4 w-full"
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

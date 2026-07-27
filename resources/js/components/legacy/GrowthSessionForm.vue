<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import { AnydesksApi } from '@/services/AnydesksApi';
import { DiscordChannelApi } from '@/services/DiscordChannelApi';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { TagsApi } from '@/services/TagsApi';
import { IGrowthSession, IStoreGrowthSessionRequest, IUser, IValidationError } from '@/types';
import { IDropdownOption } from '@/types/IDropdownOption';
import Multiselect from '@vueform/multiselect';
import { computed, onBeforeMount, ref, watch } from 'vue';
import ConfirmationModal from './ConfirmationModal.vue';
import TimePicker from './TimePicker.vue';
import VSelect from './VSelect.vue';

interface IProps {
    owner: IUser;
    growthSession?: IGrowthSession;
    startDate?: string;
}

const props = withDefaults(defineProps<IProps>(), { startDate: '' });
const emit = defineEmits(['submitted']);

const startTime = ref<string>('03:30 pm');
const endTime = ref<string>('05:00 pm');
const location = ref<string>('');
const title = ref<string>('');
const attendeeLimit = ref<number>(4);
const topic = ref<string>('');
const date = ref<string>('');
const isPublic = ref<boolean>(false);
const validationErrors = ref<IValidationError | null>(null);
const isLimitless = ref<boolean>(false);
const allowWatchers = ref<boolean>(true);
const selectedDiscordChannelId = ref<string | null>(null);
const discordChannels = ref<IDropdownOption[]>([]);
const selectedAnydeskId = ref<string | null>(null);
const anyDesks = ref<IDropdownOption[]>([]);
const anydesksToggle = ref<boolean>(false);
const tagIds = ref<string[]>([]);
const tagOptions = ref<any>({});
const tagsOpen = ref<boolean>(false);
const publicConfirmationModalState = ref<'open' | 'closed'>('closed');

const isCreating = computed(() => !props.growthSession?.id);
const requiresPublicConfirmation = computed(() => {
    const isNewPublicSession = isCreating.value && isPublic.value;
    const isBeingMadePublic = !isCreating.value && isPublic.value && !props.growthSession?.is_public;
    return isNewPublicSession || isBeingMadePublic;
});
const isReadyToSubmit = computed(() => !!startTime.value && !!endTime.value && !!date.value && !!location.value && !!topic.value && !!title.value);
const storeOrUpdatePayload = computed<IStoreGrowthSessionRequest>(() => ({
    location: location.value,
    topic: topic.value,
    title: title.value,
    date: date.value,
    start_time: startTime.value,
    end_time: endTime.value,
    is_public: isPublic.value,
    attendee_limit: isLimitless.value ? undefined : attendeeLimit.value,
    discord_channel_id: selectedDiscordChannelId.value ?? undefined,
    anydesk_id: selectedAnydeskId.value ? Number.parseInt(selectedAnydeskId.value) : undefined,
    allow_watchers: allowWatchers.value,
    tags: tagIds.value.map((tag) => +tag),
}));

onBeforeMount(() => {
    anydesksToggle.value = !!props.growthSession?.anydesk;

    date.value = props.startDate;

    getDiscordChannels();

    getAnyDesks();

    getTags();

    if (props.growthSession) {
        tagsOpen.value = props.growthSession.tags.length > 0;
        date.value = props.growthSession.date;
        startTime.value = DateTime.parseByTime(props.growthSession.start_time).toTimeString12Hours();
        endTime.value = DateTime.parseByTime(props.growthSession.end_time).toTimeString12Hours();
        location.value = props.growthSession.location;
        title.value = props.growthSession.title;
        topic.value = props.growthSession.topic;
        isLimitless.value = !props.growthSession.attendee_limit;
        attendeeLimit.value = props.growthSession.attendee_limit || 4;
        isPublic.value = props.growthSession.is_public;
        selectedAnydeskId.value = props.growthSession.anydesk?.id.toString() ?? null;
        allowWatchers.value = props.growthSession.allow_watchers;
        tagIds.value = props.growthSession.tags.map((tag) => tag.id.toString());
    }
});

function onSubmit() {
    if (requiresPublicConfirmation.value) {
        publicConfirmationModalState.value = 'open';
        return;
    }
    proceedWithSubmit();
}

function proceedWithSubmit() {
    if (isCreating.value) {
        return createGrowthSession();
    }
    updateGrowthSession();
}

function onPublicConfirmed() {
    publicConfirmationModalState.value = 'closed';
    proceedWithSubmit();
}

function onPublicDismissed() {
    publicConfirmationModalState.value = 'closed';
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

async function createGrowthSession() {
    try {
        const payload = storeOrUpdatePayload.value;
        const growthSession: IGrowthSession = await GrowthSessionApi.store(payload);
        emit('submitted', growthSession);
    } catch (e) {
        onRequestFailed(e);
    }
}

async function updateGrowthSession() {
    if (!props.growthSession) {
        return;
    }

    try {
        const growthSession: IGrowthSession = await GrowthSessionApi.update(props.growthSession, storeOrUpdatePayload.value);
        emit('submitted', growthSession);
    } catch (e) {
        onRequestFailed(e);
    }
}

async function getDiscordChannels() {
    try {
        const discordChannelsFromApi = await DiscordChannelApi.index();
        const occupiedFromApi = await DiscordChannelApi.occupied(date.value);
        const occupiedChannelIds = occupiedFromApi.map((discordChannel) => discordChannel.id);

        discordChannels.value = discordChannelsFromApi
            .map((discordChannel) => {
                return {
                    label: discordChannel.name,
                    value: discordChannel.id,
                };
            })
            .filter((discordChannel) => !occupiedChannelIds.includes(discordChannel.value));
    } catch (e) {
        onRequestFailed(e);
    }
}

async function getAnyDesks() {
    try {
        const anyDesksFromApi = await AnydesksApi.getAllAnyDesks();
        anyDesks.value = anyDesksFromApi.map((anyDesk) => {
            return {
                label: anyDesk.name,
                value: anyDesk.id.toString(),
            };
        });
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

watch(selectedDiscordChannelId, (selectedId: string | null) => {
    if (!selectedId) {
        return;
    }
    if (!location.value || location.value.startsWith('Discord Channel: ')) {
        const discordChannelName = discordChannels.value.find((channel) => channel.value === selectedId)?.label;
        location.value = `Discord Channel: ${discordChannelName}`;
    }
});
</script>

<template>
    <form @submit.prevent class="create-growth-session edit-growth-session-form w-full pt-8 text-left">
        <div class="flex flex-col gap-3.5">
            <div>
                <label class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.05em] uppercase" for="title">Title</label>
                <input
                    id="title"
                    v-model="title"
                    :class="{ 'error-outline': getError('title') }"
                    class="gs-input w-full rounded-lg px-3 py-2.5 text-sm"
                    maxlength="45"
                    placeholder="In a short sentence, what is this growth session about?"
                    type="text"
                />
            </div>

            <div>
                <label class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.05em] uppercase" for="topic">Topic</label>
                <textarea
                    id="topic"
                    v-model="topic"
                    :class="{ 'error-outline': getError('topic') }"
                    class="gs-input w-full rounded-lg px-3 py-2.5 text-sm"
                    placeholder="Any more details?"
                    rows="3"
                />
            </div>

            <div class="grid grid-cols-3 gap-2.5">
                <div>
                    <label class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.05em] uppercase" for="date">Date</label>
                    <input
                        id="date"
                        v-model="date"
                        :class="{ 'error-outline': getError('date') }"
                        class="gs-input w-full rounded-lg px-2.5 py-2 text-xs"
                        type="date"
                    />
                </div>
                <div>
                    <label class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.05em] uppercase">Start</label>
                    <time-picker
                        id="start-time"
                        v-model="startTime"
                        :class="{ 'error-outline': getError('start_time') }"
                        class="gs-input w-full rounded-lg px-2.5 py-2 text-xs"
                    />
                </div>
                <div>
                    <label class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.05em] uppercase">End</label>
                    <time-picker
                        id="end-time"
                        v-model="endTime"
                        :class="{ 'error-outline': getError('end_time') }"
                        class="gs-input w-full rounded-lg px-2.5 py-2 text-xs"
                    />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5">
                <label class="gs-text-strong flex cursor-pointer items-center gap-1.5 text-xs font-medium">
                    <input id="is-public" v-model="isPublic" type="checkbox" class="accent-gs-accent" /> Public
                </label>
                <label class="gs-text-strong flex cursor-pointer items-center gap-1.5 text-xs font-medium">
                    <input id="no-limit" v-model="isLimitless" type="checkbox" class="accent-gs-accent" /> No limit
                </label>
                <label class="gs-text-strong flex cursor-pointer items-center gap-1.5 text-xs font-medium">
                    <input id="allow-watchers" v-model="allowWatchers" type="checkbox" class="accent-gs-accent" /> Allow watchers
                </label>
                <div v-if="!isLimitless" class="ml-auto flex items-center gap-1.5">
                    <label class="gs-text-sub text-xs font-bold tracking-[0.05em] uppercase" for="attendee-limit">Limit</label>
                    <input
                        id="attendee-limit"
                        v-model.number="attendeeLimit"
                        :class="{ 'error-outline': getError('limit') }"
                        class="gs-input w-14 rounded-md px-2 py-1.5 text-center text-xs"
                        min="2"
                        placeholder="Limit"
                        type="number"
                    />
                </div>
            </div>

            <div v-if="anyDesks.length > 0" class="flex flex-wrap items-center justify-between gap-3">
                <label class="gs-text-strong flex cursor-pointer items-center gap-1.5 text-xs font-medium">
                    <input id="anydesks-toggle" v-model="anydesksToggle" type="checkbox" class="accent-gs-accent" @input="selectedAnydeskId = null" />
                    Plan to use an AnyDesk?
                </label>
                <label v-if="anydesksToggle" class="gs-text-sub flex items-center gap-2 text-xs font-bold tracking-[0.05em] uppercase">
                    AnyDesk
                    <v-select id="anydesk-selection" v-model="selectedAnydeskId" :options="anyDesks" class="w-32" />
                </label>
            </div>

            <div v-if="discordChannels.length > 0">
                <label class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.05em] uppercase">Discord Channel</label>
                <v-select id="discord-channel" v-model="selectedDiscordChannelId" :options="discordChannels" class="w-full" />
            </div>

            <div>
                <label class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.05em] uppercase" for="location">Location</label>
                <textarea
                    id="location"
                    v-model="location"
                    :class="{ 'error-outline': getError('location') }"
                    class="gs-input w-full rounded-lg px-3 py-2.5 text-sm"
                    placeholder="Where do people go?"
                    rows="2"
                />
            </div>

            <div class="gs-border border-t pt-3.5">
                <button type="button" class="gs-text-sub inline-flex items-center gap-1.5 text-xs font-semibold" @click="tagsOpen = !tagsOpen">
                    + Add tags <span class="gs-text-muted font-normal">(optional)</span>
                    <span
                        v-if="tagIds.length"
                        class="gs-header-bg inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-xs font-bold text-white"
                        >{{ tagIds.length }}</span
                    >
                </button>
                <div v-show="tagsOpen" class="mt-2.5">
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
                </div>
            </div>

            <button
                :class="{ 'cursor-not-allowed opacity-40': !isReadyToSubmit }"
                :disabled="!isReadyToSubmit"
                class="gs-btn-primary mt-1.5 w-full rounded-md py-3 text-sm font-bold"
                type="submit"
                @click="onSubmit"
                v-text="isCreating ? 'Create Session' : 'Update Session'"
            />
        </div>

        <ConfirmationModal
            v-if="publicConfirmationModalState === 'open'"
            state="open"
            title="Make Session Public?"
            message="Public sessions are visible to anyone outside Vehikl. Are you sure you want to make this session public?"
            confirm-label="Yes, make public"
            @confirmed="onPublicConfirmed"
            @dismissed="onPublicDismissed"
        />
    </form>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>

<style lang="scss" scoped>
.error-outline {
    outline: red solid 2px;
}
</style>

<style>
#anydesk .vs__dropdown-menu {
    max-height: 150px;
}
</style>

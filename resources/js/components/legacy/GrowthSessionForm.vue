<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import { AnydesksApi } from '@/services/AnydesksApi';
import { DiscordChannelApi } from '@/services/DiscordChannelApi';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { TagsApi } from '@/services/TagsApi';
import { IGrowthSession, IStoreGrowthSessionRequest, IUser, IValidationError } from '@/types';
import { IDropdownOption } from '@/types/IDropdownOption';
import { computed, nextTick, onBeforeMount, onMounted, ref, watch } from 'vue';
import ConfirmationModal from './ConfirmationModal.vue';
import TimePicker from './TimePicker.vue';
import VSelect from './VSelect.vue';

interface IProps {
    owner: IUser;
    growthSession?: IGrowthSession | null;
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
const hasInviteLink = ref<boolean>(false);
const validationErrors = ref<IValidationError | null>(null);
const isLimitless = ref<boolean>(false);
const allowWatchers = ref<boolean>(true);
const selectedDiscordChannelId = ref<string | null>(null);
const discordChannels = ref<IDropdownOption[]>([]);
const selectedAnydeskId = ref<string>('');
const anyDesks = ref<IDropdownOption[]>([]);
const tagIds = ref<string[]>([]);
const tagOptions = ref<{ label: string; value: string }[]>([]);
const showTags = ref<boolean>(true);
const titleInput = ref<HTMLInputElement | null>(null);

// Public and an invitation link are mutually exclusive: a public session is already visible to everyone. Deriving the
// effective value rather than clearing the toggle means a trip through Public and back leaves the owner's link intact.
const inviteLinkEnabled = computed(() => hasInviteLink.value && !isPublic.value);

function onInviteLinkToggled(event: Event) {
    hasInviteLink.value = (event.target as HTMLInputElement).checked;
}

function toggleTag(value: string) {
    tagIds.value = tagIds.value.includes(value) ? tagIds.value.filter((v) => v !== value) : [...tagIds.value, value];
}
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
    has_invite_link: inviteLinkEnabled.value,
    tags: tagIds.value.map((tag) => +tag),
}));

onBeforeMount(() => {
    date.value = props.startDate;

    getDiscordChannels();

    getAnyDesks();

    getTags();

    if (props.growthSession) {
        date.value = props.growthSession.date;
        startTime.value = DateTime.parseByTime(props.growthSession.start_time).toTimeString12Hours();
        endTime.value = DateTime.parseByTime(props.growthSession.end_time).toTimeString12Hours();
        location.value = props.growthSession.location;
        selectedDiscordChannelId.value = props.growthSession.discord_channel_id;
        title.value = props.growthSession.title;
        topic.value = props.growthSession.topic;
        isLimitless.value = !props.growthSession.attendee_limit;
        attendeeLimit.value = props.growthSession.attendee_limit || 4;
        isPublic.value = props.growthSession.is_public;
        // The share URL is the only signal of a live link that reaches the client — the token itself is never serialized.
        hasInviteLink.value = !!props.growthSession.share_url;
        selectedAnydeskId.value = props.growthSession.anydesk?.id.toString() ?? '';
        allowWatchers.value = props.growthSession.allow_watchers;
        tagIds.value = props.growthSession.tags.map((tag) => tag.id.toString());
    }
});

onMounted(() => {
    nextTick(() => titleInput.value?.focus());
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
    if (!location.value || location.value.startsWith('Channel: ')) {
        const discordChannelName = discordChannels.value.find((channel) => channel.value === selectedId)?.label;
        location.value = `Channel: ${discordChannelName}`;
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
                    ref="titleInput"
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

            <div class="gs-border border-t pt-4">
                <div class="gs-text-muted mb-3.5 text-xs font-bold tracking-[0.06em] uppercase">Where to meet</div>
                <div class="grid gap-2.5 sm:grid-cols-3">
                    <div v-if="anyDesks.length > 0">
                        <label class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.05em] uppercase" for="anydesk-selection">AnyDesk</label>
                        <v-select
                            id="anydesk-selection"
                            v-model="selectedAnydeskId"
                            :options="anyDesks"
                            placeholder="None"
                            clearable
                            class="w-full"
                        />
                    </div>
                    <div v-if="discordChannels.length > 0">
                        <label class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.05em] uppercase">Discord</label>
                        <v-select
                            id="discord-channel"
                            v-model="selectedDiscordChannelId"
                            :options="discordChannels"
                            placeholder="No channel"
                            clearable
                            class="w-full"
                        />
                    </div>
                    <div>
                        <label class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.05em] uppercase" for="location">Location</label>
                        <input
                            id="location"
                            v-model="location"
                            :class="{ 'error-outline': getError('location') }"
                            class="gs-input w-full rounded-lg px-3 py-2.5 text-sm"
                            placeholder="Where do people go?"
                            type="text"
                        />
                    </div>
                </div>
            </div>

            <div class="gs-border border-t pt-4">
                <div class="gs-text-muted mb-3.5 text-xs font-bold tracking-[0.06em] uppercase">Session Options</div>
                <div class="flex flex-col gap-3.5">
                    <label class="flex cursor-pointer items-center justify-between">
                        <span class="gs-text-strong text-sm font-medium">Public</span>
                        <span class="relative inline-flex flex-none">
                            <input id="is-public" v-model="isPublic" type="checkbox" class="peer sr-only" />
                            <span
                                class="peer-checked:bg-gs-accent dark:peer-checked:bg-gs-accent peer-focus-visible:ring-gs-accent h-6 w-11 rounded-full bg-black/15 transition-colors peer-focus-visible:ring-2 dark:bg-white/20"
                            ></span>
                            <span
                                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"
                            ></span>
                        </span>
                    </label>

                    <div>
                        <label class="flex items-center justify-between" :class="isPublic ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'">
                            <span class="gs-text-strong text-sm font-medium">Shareable invite link</span>
                            <span class="relative inline-flex flex-none">
                                <input
                                    id="has-invite-link"
                                    :checked="inviteLinkEnabled"
                                    :disabled="isPublic"
                                    :aria-describedby="isPublic ? 'invite-link-unavailable-reason' : undefined"
                                    type="checkbox"
                                    class="peer sr-only"
                                    @change="onInviteLinkToggled"
                                />
                                <span
                                    class="peer-checked:bg-gs-accent dark:peer-checked:bg-gs-accent peer-focus-visible:ring-gs-accent h-6 w-11 rounded-full bg-black/15 transition-colors peer-focus-visible:ring-2 dark:bg-white/20"
                                ></span>
                                <span
                                    class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"
                                ></span>
                            </span>
                        </label>
                        <p v-if="isPublic" id="invite-link-unavailable-reason" class="gs-text-muted mt-1.5 text-xs">
                            Public growth sessions are already visible to everyone.
                        </p>
                    </div>

                    <label class="flex cursor-pointer items-center justify-between">
                        <span class="gs-text-strong text-sm font-medium">Allow watchers</span>
                        <span class="relative inline-flex flex-none">
                            <input id="allow-watchers" v-model="allowWatchers" type="checkbox" class="peer sr-only" />
                            <span
                                class="peer-checked:bg-gs-accent dark:peer-checked:bg-gs-accent peer-focus-visible:ring-gs-accent h-6 w-11 rounded-full bg-black/15 transition-colors peer-focus-visible:ring-2 dark:bg-white/20"
                            ></span>
                            <span
                                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"
                            ></span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-center justify-between">
                        <span class="gs-text-strong text-sm font-medium">No limit</span>
                        <span class="relative inline-flex flex-none">
                            <input id="no-limit" v-model="isLimitless" type="checkbox" class="peer sr-only" />
                            <span
                                class="peer-checked:bg-gs-accent dark:peer-checked:bg-gs-accent peer-focus-visible:ring-gs-accent h-6 w-11 rounded-full bg-black/15 transition-colors peer-focus-visible:ring-2 dark:bg-white/20"
                            ></span>
                            <span
                                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"
                            ></span>
                        </span>
                    </label>

                    <div v-if="!isLimitless" class="flex items-center justify-between">
                        <span class="gs-text-strong text-sm font-medium">Attendee limit</span>
                        <input
                            id="attendee-limit"
                            v-model.number="attendeeLimit"
                            :class="{ 'error-outline': getError('limit') }"
                            class="gs-input w-16 rounded-md px-2 py-1.5 text-center text-sm"
                            min="2"
                            placeholder="Limit"
                            type="number"
                        />
                    </div>
                </div>
            </div>

            <div class="gs-border border-t pt-3.5">
                <button
                    type="button"
                    class="gs-text-sub inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold uppercase"
                    :aria-expanded="showTags"
                    @click="showTags = !showTags"
                >
                    + Add tags
                    <span class="gs-text-muted font-normal lowercase">(optional)</span>
                    <span
                        v-if="tagIds.length"
                        class="gs-header-bg inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-xs font-bold text-white"
                        >{{ tagIds.length }}</span
                    >
                </button>
                <div v-show="showTags" class="mt-3 flex flex-wrap gap-2">
                    <button
                        v-for="option in tagOptions"
                        :key="option.value"
                        :data-testid="`tag-option-${option.value}`"
                        type="button"
                        class="transition-smooth cursor-pointer rounded-full border px-3.5 py-2 text-xs font-semibold tracking-[0.05em] uppercase"
                        :class="
                            tagIds.includes(option.value)
                                ? 'gs-header-bg border-transparent text-white'
                                : 'gs-border hover:border-gs-accent hover:bg-gs-accent/10 hover:text-gs-accent text-gs-header/70 dark:text-gray-300'
                        "
                        @click="toggleTag(option.value)"
                    >
                        {{ option.label }}
                    </button>
                </div>
            </div>

            <button
                :class="{ 'cursor-pointer': isReadyToSubmit, 'cursor-not-allowed opacity-40': !isReadyToSubmit }"
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
<style lang="scss" scoped>
.error-outline {
    outline: red solid 2px;
}

/* Hide the native number-input spinner arrows on the attendee limit field */
#attendee-limit::-webkit-inner-spin-button,
#attendee-limit::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

#attendee-limit {
    -moz-appearance: textfield;
    appearance: textfield;
}
</style>

<style>
#anydesk .vs__dropdown-menu {
    max-height: 150px;
}
</style>

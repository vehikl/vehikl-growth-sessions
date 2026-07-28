<script lang="ts" setup>
import { AnydesksApi } from '@/services/AnydesksApi';
import { DiscordChannelApi } from '@/services/DiscordChannelApi';
import { GrowthSessionProposalApi } from '@/services/GrowthSessionProposalApi';
import { IGrowthSessionProposal, IValidationError } from '@/types';
import { IDropdownOption } from '@/types/IDropdownOption';
import { computed, onBeforeMount, ref } from 'vue';
import TimePicker from './TimePicker.vue';
import VSelect from './VSelect.vue';

interface IProps {
    proposal: IGrowthSessionProposal;
}

const props = defineProps<IProps>();
const emit = defineEmits(['submitted']);

const startTime = ref<string>('03:30 pm');
const endTime = ref<string>('05:00 pm');
const location = ref<string>('');
const date = ref<string>('');
const isPublic = ref<boolean>(false);
const attendeeLimit = ref<number>(4);
const isLimitless = ref<boolean>(false);
const allowWatchers = ref<boolean>(true);
const selectedDiscordChannelId = ref<string | null>(null);
const discordChannels = ref<IDropdownOption[]>([]);
const selectedAnydeskId = ref<string | null>(null);
const anyDesks = ref<IDropdownOption[]>([]);
const validationErrors = ref<IValidationError | null>(null);

const isReadyToSubmit = computed(() => !!startTime.value && !!endTime.value && !!date.value && !!location.value);

const approvePayload = computed(() => ({
    title: props.proposal.title,
    topic: props.proposal.topic,
    location: location.value,
    date: date.value,
    start_time: startTime.value,
    end_time: endTime.value,
    is_public: isPublic.value,
    attendee_limit: isLimitless.value ? undefined : attendeeLimit.value,
    discord_channel_id: selectedDiscordChannelId.value ?? undefined,
    anydesk_id: selectedAnydeskId.value ? Number.parseInt(selectedAnydeskId.value) : undefined,
    allow_watchers: allowWatchers.value,
}));

onBeforeMount(() => {
    getDiscordChannels();
    getAnyDesks();
});

async function onSubmit() {
    try {
        await GrowthSessionProposalApi.approve(props.proposal.id, approvePayload.value);
        emit('submitted');
    } catch (e: any) {
        if (e.response?.status === 422) {
            validationErrors.value = e.response.data;
        } else {
            alert('Something went wrong :(');
        }
    }
}

function getError(field: string): string {
    const errors = validationErrors.value?.errors[field];
    return errors ? errors[0] : '';
}

function formatTimePreferences(): string {
    return props.proposal.time_preferences.map((pref) => `${pref.weekday}: ${pref.start_time} - ${pref.end_time}`).join(', ');
}

async function getDiscordChannels() {
    try {
        const discordChannelsFromApi = await DiscordChannelApi.index();
        discordChannels.value = discordChannelsFromApi.map((discordChannel) => {
            return {
                label: discordChannel.name,
                value: discordChannel.id,
            };
        });
    } catch (e) {
        console.error(e);
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
        console.error(e);
    }
}
</script>

<template>
    <form @submit.prevent="onSubmit" class="approve-form max-h-[95vh] w-full overflow-y-auto bg-white p-4 pt-10 text-left">
        <div class="mb-6 rounded bg-blue-50 p-4">
            <h3 class="mb-2 text-lg font-bold text-blue-900">Approving Proposal</h3>
            <p class="mb-2 text-sm text-blue-800"><strong>Title:</strong> {{ proposal.title }}</p>
            <p class="mb-2 text-sm text-blue-800"><strong>Topic:</strong> {{ proposal.topic }}</p>
            <p class="mb-2 text-sm text-blue-800"><strong>Preferred Times:</strong> {{ formatTimePreferences() }}</p>
            <p class="text-sm text-blue-800"><strong>Proposed by:</strong> {{ proposal.creator.name }}</p>
        </div>

        <div class="mb-4 grid grid-cols-3 gap-6 rounded bg-slate-100 p-4">
            <label :class="{ 'error-outline': getError('date') }" class="block text-sm font-bold tracking-wide text-slate-700 uppercase">
                Date
                <input id="date" v-model="date" class="mt-1 block w-full border border-slate-400 p-2" type="date" />
            </label>

            <label class="text-sm font-bold tracking-wide text-slate-700 uppercase">
                Start
                <time-picker
                    id="start-time"
                    v-model="startTime"
                    :class="{ 'error-outline': getError('start_time') }"
                    class="mt-1 block w-full border border-slate-400 p-2"
                />
            </label>

            <label class="text-sm font-bold tracking-wide text-slate-700 uppercase">
                End
                <time-picker
                    id="end-time"
                    v-model="endTime"
                    :class="{ 'error-outline': getError('end_time') }"
                    class="mt-1 block w-full border border-slate-400 p-2"
                />
            </label>
        </div>

        <div class="mb-4 rounded bg-slate-100 p-4 py-6">
            <div class="mb-4 grid grid-cols-2 gap-6">
                <div class="grid grid-rows-2 gap-2">
                    <label class="flex items-center text-sm font-bold tracking-wide text-slate-700 uppercase">
                        <input id="is-public" v-model="isPublic" type="checkbox" class="mr-2" /> Is Public
                    </label>

                    <label class="flex items-center text-sm font-bold tracking-wide text-slate-700 uppercase">
                        <input id="no-limit" v-model="isLimitless" type="checkbox" class="mr-2" /> No Limit
                    </label>
                </div>

                <div v-if="!isLimitless" class="flex items-center">
                    <label class="text-sm font-bold tracking-wide text-slate-700 uppercase">
                        Limit
                        <input
                            id="attendee-limit"
                            v-model.number="attendeeLimit"
                            :class="{ 'error-outline': getError('limit') }"
                            class="focus:shadow-outline block w-full appearance-none border border-slate-400 px-3 py-1 text-center leading-tight text-slate-700 shadow focus:outline-none"
                            min="2"
                            placeholder="Limit of participants"
                            type="number"
                        />
                    </label>
                </div>
            </div>

            <label class="flex items-center text-sm font-bold tracking-wide text-slate-700 uppercase">
                <input id="allow-watchers" v-model="allowWatchers" type="checkbox" class="mr-2" /> Allow watchers
            </label>
        </div>

        <div v-if="anyDesks.length > 0" class="mb-4">
            <label class="mb-2 block text-sm font-bold tracking-wide text-slate-700 uppercase">
                Anydesk (Optional)
                <v-select id="anydesk-selection" v-model="selectedAnydeskId" :options="anyDesks" class="mt-1" />
            </label>
        </div>

        <div v-if="discordChannels.length > 0" class="mb-4">
            <label class="mb-2 block text-sm font-bold tracking-wide text-slate-700 uppercase">
                Discord Channel (Optional)
                <v-select id="discord-channel" v-model="selectedDiscordChannelId" :options="discordChannels" class="mt-1" />
            </label>
        </div>

        <div class="mb-6">
            <label class="mb-1 block text-sm font-bold tracking-wide text-slate-700 uppercase" for="location"> Location </label>
            <textarea
                id="location"
                v-model="location"
                :class="{ 'error-outline': getError('location') }"
                class="focus:shadow-outline w-full appearance-none border border-slate-400 px-3 py-2 leading-tight text-slate-700 shadow focus:outline-none"
                placeholder="Where should people go to participate?"
                rows="2"
            />
        </div>

        <button
            :class="{ 'cursor-pointer': isReadyToSubmit, 'cursor-not-allowed opacity-25': !isReadyToSubmit }"
            :disabled="!isReadyToSubmit"
            class="w-full border-4 border-green-600 bg-white px-4 py-2 font-bold text-green-600 hover:bg-green-600 hover:text-white focus:bg-green-700"
            type="submit"
        >
            Approve & Create Growth Session
        </button>
    </form>
</template>

<style lang="scss" scoped>
.error-outline {
    outline: red solid 2px;
}
</style>

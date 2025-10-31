<script lang="ts" setup>
import { IGrowthSessionProposal, IValidationError } from '@/types';
import { GrowthSessionProposalApi } from '@/services/GrowthSessionProposalApi';
import { DiscordChannelApi } from '@/services/DiscordChannelApi';
import { IDropdownOption } from '@/types/IDropdownOption';
import { AnydesksApi } from '@/services/AnydesksApi';
import TimePicker from './TimePicker.vue';
import { computed, onBeforeMount, ref } from 'vue';
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
    let errors = validationErrors.value?.errors[field];
    return errors ? errors[0] : '';
}

function formatTimePreferences(): string {
    return props.proposal.time_preferences
        .map((pref) => `${pref.weekday}: ${pref.start_time} - ${pref.end_time}`)
        .join(', ');
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
    <form @submit.prevent="onSubmit" class="approve-form bg-white w-full p-4 pt-10 text-left overflow-y-auto max-h-[95vh]">
        <div class="mb-6 p-4 bg-blue-50 rounded">
            <h3 class="text-lg font-bold text-blue-900 mb-2">Approving Proposal</h3>
            <p class="text-sm text-blue-800 mb-2">
                <strong>Title:</strong> {{ proposal.title }}
            </p>
            <p class="text-sm text-blue-800 mb-2">
                <strong>Topic:</strong> {{ proposal.topic }}
            </p>
            <p class="text-sm text-blue-800 mb-2">
                <strong>Preferred Times:</strong> {{ formatTimePreferences() }}
            </p>
            <p class="text-sm text-blue-800">
                <strong>Proposed by:</strong> {{ proposal.creator.name }}
            </p>
        </div>

        <div class="mb-4 grid grid-cols-3 gap-6 bg-slate-100 p-4 rounded">
            <label :class="{ 'error-outline': getError('date') }" class="block text-slate-700 text-sm uppercase tracking-wide font-bold">
                Date
                <input id="date" v-model="date" class="w-full block border p-2 mt-1 border-slate-400" type="date" />
            </label>

            <label class="text-slate-700 text-sm uppercase tracking-wide font-bold">
                Start
                <time-picker
                    id="start-time"
                    v-model="startTime"
                    :class="{ 'error-outline': getError('start_time') }"
                    class="block border p-2 mt-1 border-slate-400 w-full"
                />
            </label>

            <label class="text-slate-700 text-sm uppercase tracking-wide font-bold">
                End
                <time-picker
                    id="end-time"
                    v-model="endTime"
                    :class="{ 'error-outline': getError('end_time') }"
                    class="block border p-2 mt-1 border-slate-400 w-full"
                />
            </label>
        </div>

        <div class="p-4 py-6 bg-slate-100 rounded mb-4">
            <div class="mb-4 grid grid-cols-2 gap-6">
                <div class="grid grid-rows-2 gap-2">
                    <label class="flex text-slate-700 text-sm uppercase tracking-wide font-bold items-center">
                        <input id="is-public" v-model="isPublic" type="checkbox" class="mr-2" /> Is Public
                    </label>

                    <label class="flex text-slate-700 text-sm uppercase tracking-wide font-bold items-center">
                        <input id="no-limit" v-model="isLimitless" type="checkbox" class="mr-2" /> No Limit
                    </label>
                </div>

                <div v-if="!isLimitless" class="flex items-center">
                    <label class="text-slate-700 text-sm uppercase tracking-wide font-bold">
                        Limit
                        <input
                            id="attendee-limit"
                            v-model.number="attendeeLimit"
                            :class="{ 'error-outline': getError('limit') }"
                            class="block w-full text-center shadow appearance-none border border-slate-400 py-1 px-3 text-slate-700 leading-tight focus:outline-none focus:shadow-outline"
                            min="2"
                            placeholder="Limit of participants"
                            type="number"
                        />
                    </label>
                </div>
            </div>

            <label class="flex items-center text-slate-700 text-sm uppercase tracking-wide font-bold">
                <input id="allow-watchers" v-model="allowWatchers" type="checkbox" class="mr-2" /> Allow watchers
            </label>
        </div>

        <div v-if="anyDesks.length > 0" class="mb-4">
            <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-2">
                Anydesk (Optional)
                <v-select id="anydesk-selection" v-model="selectedAnydeskId" :options="anyDesks" class="mt-1" />
            </label>
        </div>

        <div v-if="discordChannels.length > 0" class="mb-4">
            <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-2">
                Discord Channel (Optional)
                <v-select id="discord-channel" v-model="selectedDiscordChannelId" :options="discordChannels" class="mt-1" />
            </label>
        </div>

        <div class="mb-6">
            <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-1" for="location">
                Location
            </label>
            <textarea
                id="location"
                v-model="location"
                :class="{ 'error-outline': getError('location') }"
                class="shadow appearance-none border border-slate-400 w-full py-2 px-3 text-slate-700 leading-tight focus:outline-none focus:shadow-outline"
                placeholder="Where should people go to participate?"
                rows="2"
            />
        </div>

        <button
            :class="{ 'opacity-25 cursor-not-allowed': !isReadyToSubmit }"
            :disabled="!isReadyToSubmit"
            class="border-green-600 hover:bg-green-600 focus:bg-green-700 text-green-600 border-4 bg-white hover:text-white font-bold py-2 px-4 w-full"
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

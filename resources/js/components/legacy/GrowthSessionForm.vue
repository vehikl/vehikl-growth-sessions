<script lang="ts" setup>
import {IGrowthSession, IStoreGrowthSessionRequest, IUser, IValidationError} from "@/types"
import {GrowthSessionApi} from "@/services/GrowthSessionApi"
import {DateTime} from "@/classes/DateTime"
import {DiscordChannelApi} from "@/services/DiscordChannelApi"
import {IDropdownOption} from "@/types/IDropdownOption"
import {AnydesksApi} from "@/services/AnydesksApi"
import TimePicker from "./TimePicker.vue"
import {computed, onBeforeMount, ref, watch} from "vue"
import VSelect from "./VSelect.vue"
import Multiselect from "@vueform/multiselect"
import {TagsApi} from "@/services/TagsApi";

interface IProps {
    owner: IUser;
    growthSession?: IGrowthSession;
    startDate?: string;
}

const props = withDefaults(defineProps<IProps>(), {startDate: ""})
const emit = defineEmits(["submitted"])

const startTime = ref<string>("03:30 pm")
const endTime = ref<string>("05:00 pm")
const location = ref<string>("")
const title = ref<string>("")
const attendeeLimit = ref<number>(4)
const topic = ref<string>("")
const date = ref<string>("")
const isPublic = ref<boolean>(false)
const validationErrors = ref<IValidationError | null>(null)
const isLimitless = ref<boolean>(false)
const allowWatchers = ref<boolean>(true)
const selectedDiscordChannelId = ref<string | null>(null)
const discordChannels = ref<IDropdownOption[]>([])
const selectedAnydeskId = ref<string | null>(null)
const anyDesks = ref<IDropdownOption[]>([])
const anydesksToggle = ref<boolean>(false)
const tagIds = ref<string[]>([])
const tagOptions = ref<any>({})

const isCreating = computed(() => !props.growthSession?.id)
const isReadyToSubmit = computed(() => !!startTime.value
    && !!endTime.value
    && !!date.value
    && !!location.value
    && !!topic.value
    && !!title.value
)
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
    tags: tagIds.value.map((tag) => +tag)
}))

onBeforeMount(() => {
    anydesksToggle.value = !!props.growthSession?.anydesk

    date.value = props.startDate

    getDiscordChannels()

    getAnyDesks()

    getTags()

    if (props.growthSession) {
        date.value = props.growthSession.date
        startTime.value = DateTime.parseByTime(props.growthSession.start_time).toTimeString12Hours()
        endTime.value = DateTime.parseByTime(props.growthSession.end_time).toTimeString12Hours()
        location.value = props.growthSession.location
        title.value = props.growthSession.title
        topic.value = props.growthSession.topic
        isLimitless.value = !props.growthSession.attendee_limit
        attendeeLimit.value = props.growthSession.attendee_limit || 4
        isPublic.value = props.growthSession.is_public
        selectedAnydeskId.value = props.growthSession.anydesk?.id.toString() ?? null
        allowWatchers.value = props.growthSession.allow_watchers
        tagIds.value = props.growthSession.tags.map(tag => tag.id.toString())
    }
})

function onSubmit() {
    if (isCreating.value) {
        return createGrowthSession()
    }
    updateGrowthSession()
}

function onRequestFailed(exception: any) {
    if (exception.response?.status === 422) {
        validationErrors.value = exception.response.data
    } else {
        alert("Something went wrong :(")
    }
}

function getError(field: string): string {
    let errors = validationErrors.value?.errors[field]
    return errors ? errors[0] : ""
}

async function createGrowthSession() {
    try {
        const payload = storeOrUpdatePayload.value
        let growthSession: IGrowthSession = await GrowthSessionApi.store(payload)
        emit("submitted", growthSession)
    } catch (e) {
        onRequestFailed(e)
    }
}

async function updateGrowthSession() {
    if (!props.growthSession) {
        return
    }

    try {
        let growthSession: IGrowthSession = await GrowthSessionApi.update(props.growthSession, storeOrUpdatePayload.value)
        emit("submitted", growthSession)
    } catch (e) {
        onRequestFailed(e)
    }
}

async function getDiscordChannels() {
    try {
        const discordChannelsFromApi = await DiscordChannelApi.index();
        const occupiedFromApi = await DiscordChannelApi.occupied(date.value);
        const occupiedChannelIds = occupiedFromApi.map(discordChannel => discordChannel.id);

        discordChannels.value = discordChannelsFromApi.map(discordChannel => {
            return {
                label: discordChannel.name,
                value: discordChannel.id
            }
        }).filter(discordChannel => !occupiedChannelIds.includes(discordChannel.value))
    } catch (e) {
        onRequestFailed(e)
    }
}

async function getAnyDesks() {
    try {
        const anyDesksFromApi = await AnydesksApi.getAllAnyDesks()
        anyDesks.value = anyDesksFromApi.map(anyDesk => {
            return {
                label: anyDesk.name,
                value: anyDesk.id.toString()
            }
        })
    } catch (e) {
        onRequestFailed(e)
    }
}

async function getTags() {
  try {
    const tagsFromApi = await TagsApi.index()
    tagOptions.value = tagsFromApi.map(tag => {
      return {
        label: tag.name,
        value: tag.id.toString()
      }
    })
  } catch (e) {
    onRequestFailed(e)
  }
}

watch(selectedDiscordChannelId, (selectedId: string | null) => {
    if (!selectedId) {
        return
    }
    if (!location.value || location.value.startsWith("Discord Channel: ")) {
        const discordChannelName = discordChannels.value.find(channel => channel.value === selectedId)?.label
        location.value = `Discord Channel: ${discordChannelName}`
    }
})
</script>

<template>
    <form @submit.prevent class="create-growth-session edit-growth-session-form bg-white w-full p-4 pt-10 text-left">
        <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-6">
            Title
            <input
                id="title"
                v-model="title"
                :class="{ 'error-outline': getError('title') }"
                class="shadow block appearance-none border border-slate-400 w-full mt-1 py-2 px-3 text-lg font-normal text-slate-700 leading-tight focus:outline-none focus:shadow-outline"
                maxlength="45"
                placeholder="In a short sentence, what is this growth session about?"
                type="text"/>
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
                placeholder="Do you want to provide more details about this growth session?"
                rows="4"/>
        </div>

        <div class="mb-4 grid grid-cols-3 gap-6 bg-slate-100 p-4 rounded">
            <label :class="{'error-outline': getError('date')}"
                   class="block text-slate-700 text-sm uppercase tracking-wide font-bold">
                Date
                <input id="date" v-model="date" class="w-full block border p-2 mt-1 border-slate-400" type="date">
            </label>

            <label class="text-slate-700 text-sm uppercase tracking-wide font-bold">
                Start
                <time-picker id="start-time"
                                v-model="startTime"
                                :class="{'error-outline': getError('start_time')}"
                                class="block border p-2 mt-1 border-slate-400 w-full"/>
            </label>

            <label class="text-slate-700 text-sm uppercase tracking-wide font-bold">
                End
                <time-picker id="end-time" v-model="endTime" :class="{'error-outline': getError('end_time')}"
                                class="block border p-2 mt-1 border-slate-400 w-full"/>
            </label>
        </div>


        <div class="p-4 py-6 bg-slate-100 rounded">
            <div class="mb-4 grid grid-cols-2 gap-6">
                <div class="grid grid-rows-2 gap-2">
                    <label class="flex text-slate-700 text-sm uppercase tracking-wide font-bold items-center">
                        <input id="is-public" v-model="isPublic" type="checkbox" class="mr-2"> Is Public
                    </label>

                    <label class="flex text-slate-700 text-sm uppercase tracking-wide font-bold items-center">
                        <input id="no-limit" v-model="isLimitless" type="checkbox" class="mr-2">
                        No Limit
                    </label>
                </div>

                <div v-if="!isLimitless" class="flex items-center">
                    <label class="text-slate-700 text-sm uppercase tracking-wide font-bold">
                        Limit
                        <input
                            id="attendee-limit"
                            v-model.number="attendeeLimit"
                            :class="{'error-outline': getError('limit')}"
                            class="block w-full text-center shadow appearance-none border border-slate-400 py-1 px-3 text-slate-700 leading-tight focus:outline-none focus:shadow-outline"
                            min="2"
                            placeholder="Limit of participants"
                            type="number"/>
                    </label>
                </div>
            </div>

            <label class="flex items-center text-slate-700 text-sm uppercase tracking-wide font-bold">
                <input id="allow-watchers" v-model="allowWatchers" type="checkbox" class="mr-2"> Allow watchers
            </label>
        </div>

        <div v-if="anyDesks.length > 0" class="flex justify-between items-center w-full py-4">
            <div class="flex-1">
                <label class="flex items-center text-slate-700 text-sm uppercase tracking-wide font-bold">
                    <input id="anydesks-toggle"
                           v-model="anydesksToggle"
                           type="checkbox"
                           class="mr-2"
                           @input="selectedAnydeskId = null">
                    Plan to use an AnyDesk?
                </label>
            </div>
            <label v-if="anydesksToggle" class="flex-1 text-right block text-slate-700 text-sm uppercase tracking-wide font-bold">
                Anydesk
                <v-select id="anydesk-selection"
                          v-model="selectedAnydeskId"
                          :options="anyDesks" class="ml-4 w-32"/>
            </label>
        </div>
        <label class="flex flex-col text-slate-700 text-sm uppercase tracking-wide font-bold gap-1 mb-6">
            Tags
            <Multiselect
                v-model="tagIds"
                mode="tags"
                :close-on-select="false"
                :searchable="true"
                :options="tagOptions"
                :classes="{ tag: 'bg-slate-100 text-slate-700 text-sm font-semibold py-0.5 pl-2 rounded mr-1 mb-1 flex items-center whitespace-nowrap min-w-0 rtl:pl-0 rtl:pr-2 rtl:mr-0 rtl:ml-1' }"
            />
        </label>


        <div class="mb-4" v-if="(discordChannels.length > 0)">
            <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-2">
                Discord Channel
                <v-select id="discord-channel"
                          v-model="selectedDiscordChannelId"
                          :options="discordChannels"
                          class="ml-4 w-48"></v-select>
            </label>
        </div>

        <div class="mb-6">
            <label class="block text-slate-700 text-sm uppercase tracking-wide font-bold mb-1" for="location">
                Location
            </label>
            <textarea
                id="location"
                v-model="location"
                :class="{'error-outline': getError('location')}"
                class="shadow appearance-none border border-slate-400 w-full py-2 px-3 text-slate-700 leading-tight focus:outline-none focus:shadow-outline"
                placeholder="Where should people go to participate?"
                rows="2"/>
        </div>
        <button
                    :class="{ 'opacity-25 cursor-not-allowed': !isReadyToSubmit }"
                    :disabled="!isReadyToSubmit"
                    @click="onSubmit"
                    class="border-gray-600 hover:bg-gray-600 focus:bg-gray-700 text-gray-600 border-4 bg-white hover:text-white font-bold py-2 px-4 w-full"
                    type="submit"
                    ref="submit-button"
                    v-text="isCreating ? 'Create' : 'Update'">
                </button>
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

<script lang="ts" setup>
import {IGrowthSession, IUser} from "../types"
import {DateTime} from "../classes/DateTime"
import {GrowthSession} from "../classes/GrowthSession"
import CommentList from "./CommentList.vue"
import VAvatar from "./VAvatar.vue"
import LocationRenderer from "./LocationRenderer.vue"
import GrowthSessionForm from "./GrowthSessionForm.vue"
import VButton from "./VButton.vue"
import VModal from "./VModal.vue"
import {computed, ref} from "vue"
import {useGrowthSession} from "../composables/useGrowthSession"
import GrowthSessionTags from "./GrowthSessionTags.vue";

interface IProps {
    userJson?: IUser;
    growthSessionJson: IGrowthSession;
    discordGuildId?: string;
}

const props = defineProps<IProps>()
const formModalState = ref<"open" | "closed">("closed")
const {
    watchGrowthSession,
    leaveGrowthSession,
    joinGrowthSession,
    growthSession,
    isProcessing
} = useGrowthSession(props.growthSessionJson)

const parsedTopic = computed(() => {
    return growthSession?.value.topic
        .split(" ")
        .map((word => {
            if (isURL(word)) {
                const prefixedUrl = !word.includes('https') ? `https://${word}` : word;
                word = `<a target="_blank" href="${prefixedUrl}">${word}</a>`
            }
            return word
        }))
        .join(" ")
})
const date = computed(() => `${DateTime.parseByDate(growthSession.value.date).format("MMM-DD")}`)
const time = computed(() => `${growthSession.value.startTime} - ${growthSession.value.endTime}`)
const mobtimeUrl = computed(() => `https://mobtime.vehikl.com/vgs-${props.growthSessionJson.id}`)
const discordChannelUrl = computed(() => `discord://discordapp.com/channels/${props.discordGuildId}/${growthSession.value.discord_channel_id}`)

function isURL(candidate: string): boolean {
    return candidate.match(/.+\..+/g)
}

async function deleteGrowthSession() {
    if (confirm("Are you sure you want to delete?")) {
        await growthSession.value.delete()
        window.location.assign("/")
    }
}

async function onGrowthSessionUpdated(newValues: GrowthSession) {
    growthSession.value.refresh(newValues)
    formModalState.value = "closed"
}
</script>

<template>
    <div class="max-w-5xl px-4">
        <v-modal :state="formModalState" @modal-closed="formModalState = 'closed'">
            <div class="flex flex-wrap flex-row-reverse overflow-visible relative">
                <button class="bg-gray-900 absolute text-white border-4 border-gray-900 rounded-full px-4 py-1 -top-2 right-2 hover:text-gray-900 hover:bg-white x hover:border-gray-900" @click="formModalState = 'closed';">
                    <i aria-hidden="true" class="fa fa-times text-xl"></i> Close
                </button>
                <growth-session-form v-if="formModalState === 'open'"
                                        :growth-session="growthSession"
                                        :owner="growthSession.owner"
                                        :start-date="growthSession.date"
                                        class="growth-session-form"
                                        @submitted="onGrowthSessionUpdated"/>
            </div>
        </v-modal>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div>
                <a id="owner-avatar-link" :href="growthSession.owner.githubURL">
                    <div class="flex items-center justify-start flex-1 mb-2 text-gray-700">
                        <v-avatar :alt="`${growthSession.owner.name}'s Avatar`" :size="6" :src="growthSession.owner.avatar"/>
                        <p class="ml-6 text-sm font-bold tracking-wider uppercase" v-text="growthSession.owner.name"/>
                    </div>
                </a>

                <h1
                    class="text-4xl font-semibold text-gray-700 text-left mb-3 break-words"
                >
                    {{ growthSession.title }}
                </h1>
                <div class="flex justify-between mb-2 text-slate-500">
                  <div class="font-bold flex flex-col">
                    <p>{{ growthSession.date }}</p>
                    <p>{{ growthSession.startTime }} to {{ growthSession.endTime }}</p>
                    </div>
                    <div class="flex items-center attendees-count">
                        <i class="fa fa-user-circle text-lg mr-2" aria-hidden="true"></i>
                        <span v-text="growthSession.attendees.length"/>
                        <span v-if="growthSession.attendee_limit" class="pl-1 attendee-limit">of {{
                            growthSession.attendee_limit
                        }}</span>
                    </div>
                </div>

                <GrowthSessionTags :tags="growthSession.tags" class="mb-4" />

                    <pre
                        class="mb-4 topic inline-block text-left break-words-fixed whitespace-pre-wrap max-h-64 overflow-y-auto overflow-x-hidden font-sans text-slate-600 tracking-wide leading-relaxed"
                    v-html="parsedTopic"
                />

                <div class="grid grid-cols-1 gap-2">
                    <v-button
                        :class="isProcessing? 'opacity-75 cursor-not-allowed' : 'opacity-100'"
                        :disabled="isProcessing"
                        class="join-button"
                        color="blue"
                        @click="joinGrowthSession"
                        v-show="growthSession.canJoin(userJson)"
                        text="Join"/>
                    <v-button
                        :class="isProcessing? 'opacity-75 cursor-not-allowed' : 'opacity-100'"
                        :disabled="isProcessing"
                        class="watch-button"
                        color="orange"
                        @click="watchGrowthSession"
                        v-show="growthSession.canWatch(userJson)"
                        text="Spectate"/>
                    <v-button
                        :class="isProcessing? 'opacity-75 cursor-not-allowed' : 'opacity-100'"
                        :disabled="isProcessing"
                        class="leave-button"
                        color="red"
                        @click="leaveGrowthSession"
                        v-show="growthSession.canLeave(userJson)"
                        text="Leave"/>
                    <v-button
                        :class="isProcessing? 'opacity-75 cursor-not-allowed' : 'opacity-100'"
                        :disabled="isProcessing"
                        class="update-button"
                        color="orange"
                        text="Edit"
                        v-if="growthSession.canEditOrDelete(userJson)"
                        @click="formModalState = 'open'"/>
                    <v-button
                        :class="isProcessing? 'opacity-75 cursor-not-allowed' : 'opacity-100'"
                        :disabled="isProcessing"
                        color="red"
                        class="delete-button"
                        @click="deleteGrowthSession"
                        v-if="growthSession.canEditOrDelete(userJson)"
                        text="Delete"/>
                </div>
            </div>

            <div class="row-span-2">
                <div class="text-gray-600 text-left mb-4 break-all">
                    <h3 class="text-lg uppercase tracking-widest text-slate-600 font-semibold">Location</h3>
                    <i class="fa fa-compass text-xl mr-2" aria-hidden="true"></i>
                    <location-renderer :locationString="growthSession.location"/>
                </div>

                <div class="mb-4">
                    <a v-if="growthSession.discord_channel_id"
                        class="location-icon"
                        target="_blank"
                        :href="discordChannelUrl">
                        <svg enable-background="new 0 0 24 24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <g fill="#5c6bc0">
                                <path
                                    d="m3.58 21.196h14.259l-.681-2.205c.101.088 5.842 5.009 5.842 5.009v-21.525c-.068-1.338-1.22-2.475-2.648-2.475l-16.767.003c-1.427 0-2.585 1.139-2.585 2.477v16.24c0 1.411 1.156 2.476 2.58 2.476zm10.548-15.513-.033.012.012-.012zm-7.631 1.269c1.833-1.334 3.532-1.27 3.532-1.27l.137.135c-2.243.535-3.26 1.537-3.26 1.537.104-.022 4.633-2.635 10.121.066 0 0-1.019-.937-3.124-1.537l.186-.183c.291.001 1.831.055 3.479 1.26 0 0 1.844 3.15 1.844 7.02-.061-.074-1.144 1.666-3.931 1.726 0 0-.472-.534-.808-1 1.63-.468 2.24-1.404 2.24-1.404-3.173 1.998-5.954 1.686-9.281.336-.031 0-.045-.014-.061-.03v-.006c-.016-.015-.03-.03-.061-.03h-.06c-.204-.134-.34-.2-.34-.2s.609.936 2.174 1.404c-.411.469-.818 1.002-.818 1.002-2.786-.066-3.802-1.806-3.802-1.806 0-3.876 1.833-7.02 1.833-7.02z"/>
                                <path
                                    d="m14.308 12.771c.711 0 1.29-.6 1.29-1.34 0-.735-.576-1.335-1.29-1.335v.003c-.708 0-1.288.598-1.29 1.338 0 .734.579 1.334 1.29 1.334z"/>
                                <path
                                    d="m9.69 12.771c.711 0 1.29-.6 1.29-1.34 0-.735-.575-1.335-1.286-1.335l-.004.003c-.711 0-1.29.598-1.29 1.338 0 .734.579 1.334 1.29 1.334z"/>
                            </g>
                        </svg>
                    </a>
                </div>

                <div v-if="growthSession.anydesk" class="mb-4">
                    <h3 class="text-lg uppercase tracking-widest text-slate-600 font-semibold">AnyDesk</h3>
                    <span>{{ growthSession.anydesk.name }}: {{ growthSession.anydesk.remote_desk_id }}</span>
                </div>

                <div class="mb-4">
                    <h3 class="text-lg uppercase tracking-widest text-slate-600 font-semibold">MobTime</h3>
                    <a :href="mobtimeUrl" target="_blank" class="text-gray-600 break-words">{{ mobtimeUrl }}</a>
                </div>

                <div class="mb-4" v-if="growthSession.attendee_limit">
                    <h3 class="text-lg uppercase tracking-widest text-slate-600 font-semibold">Attendee Limit</h3>
                    <span class="attendee_limit">{{ growthSession.attendee_limit }}</span>
                </div>

                <h3 class="text-lg uppercase tracking-widest text-slate-600 font-semibold">Attendees</h3>
                <ul class="mb-4">
                    <li v-for="attendee in growthSession.attendees" class="py-2">
                        <a ref="attendee" :href="attendee.githubURL" class="flex items-center">
                            <v-avatar :alt="`${attendee.name}'s Avatar`" :size="6" :src="attendee.avatar"
                                        class="mr-3"/>
                            <p v-if="!attendee.is_vehikl_member" class="ml-2 text-sm font-bold tracking-wider uppercase text-vehikl-orange">{{
                                    attendee.name
                                }}</p>
                            <p v-else class="ml-2 text-sm font-bold tracking-wider uppercase text-slate-600">{{ attendee.name }}</p>
                        </a>
                    </li>
                </ul>


                <h3 v-if="growthSession.watchers.length" class="text-lg uppercase tracking-widest text-slate-600 font-semibold">
                    Watchers</h3>
                <ul>
                    <li v-for="watcher in growthSession.watchers">
                        <a ref="attendee" :href="watcher.githubURL" class="flex items-center mb-4">
                            <v-avatar :alt="`${watcher.name}'s Avatar`" :size="6" :src="watcher.avatar" class="mr-3"/>
                            <p v-if="!watcher.is_vehikl_member" class="ml-2 text-sm font-bold tracking-wider uppercase text-vehikl-orange">{{
                                    watcher.name
                                }}</p>
                            <p v-else class="ml-2 text-sm font-bold tracking-wider uppercase text-slate-600">{{ watcher.name }}</p>
                        </a>
                    </li>
                </ul>
            </div>

            <comment-list :growth-session="growthSession" :user="userJson"/>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.description {
    min-height: 10rem;
}

.location-icon svg {
    width: 24px;
    display: inline-block;
    margin-left: 4px;
}
</style>

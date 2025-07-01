<script lang="ts" setup>
import {GrowthSessionApi} from "@/services/GrowthSessionApi"
import {GrowthSession} from "@/classes/GrowthSession"
import {IUser} from "@/types"
import VAvatar from "@/components/legacy/VAvatar.vue"
import VButton from "@/components/legacy/VButton.vue"
import IconDraggable from "@/svgs/IconDraggable.vue"
import LocationRenderer from "@/components/legacy/LocationRenderer.vue"
import {computed} from "vue"
import GrowthSessionTags from "@/components/legacy/GrowthSessionTags.vue";

interface IProps {
    growthSession: GrowthSession;
    user?: IUser;
}

const props = defineProps<IProps>()
const emit = defineEmits(["growth-session-updated", "delete-requested", "edit-requested", "copy-requested"])

const isDraggable = computed<boolean>(() => !!props.user && props.growthSession.canEditOrDelete(props.user))
const growthSessionUrl = computed<string>(() => GrowthSessionApi.showUrl(props.growthSession))


async function watchGrowthSession() {
    await props.growthSession.watch()
    emit("growth-session-updated")
}

async function joinGrowthSession() {
    await props.growthSession.join()
    emit("growth-session-updated")
}

async function leaveGrowthSession() {
    await props.growthSession.leave()
    emit("growth-session-updated")
}

async function onDeleteClicked() {
    if (confirm("Are you sure you want to delete?")) {
        await props.growthSession.delete()
        emit("delete-requested", props.growthSession)
    }
}

</script>

<template>
    <a class="block border-l-4 px-4 pb-2 pt-6 mx-2 cursor-pointer overflow-visible mb-3 transform transition-transform duration-150 shadow-md bg-white"
       :class="growthSession.is_public ? 'border-slate-300' : 'border-vehikl-orange'"
       :href="growthSessionUrl">

        <div class="flex items-center justify-between flex-1 mb-2 text-gray-700">
            <p class="mr-6 text-sm font-bold tracking-wider uppercase" v-text="growthSession.owner.name"/>
            <v-avatar :alt="`${growthSession.owner.name}'s Avatar`" :size="6" :src="growthSession.owner.avatar"/>
        </div>
       <h3 class="font-semibold text-3xl text-gray-700 text-left mb-3 break-words"
        v-text="growthSession.title"/>


        <div class="flex justify-between mb-2 text-slate-500">
            <div class="font-bold">
                {{ growthSession.startTime }} to {{ growthSession.endTime }}
            </div>
            <div class="flex items-center attendees-count">
                <i class="fa fa-user-circle text-lg mr-2" aria-hidden="true"></i>
                <span v-text="growthSession.attendees.length"/>
                <span v-if="growthSession.attendee_limit" class="pl-1 attendee-limit">of {{
                        growthSession.attendee_limit
                    }}</span>
            </div>
        </div>

        <GrowthSessionTags class="mb-4" :tags="growthSession.tags" />

        <pre
            class="mb-4 topic inline-block text-left break-words-fixed whitespace-pre-wrap max-h-64 overflow-y-auto overflow-x-hidden font-sans text-slate-600 tracking-wide leading-relaxed"
            v-text="growthSession.topic"/>

        <div class="text-gray-600 text-left mb-4 break-all">
            <i class="fa fa-compass text-xl mr-2" aria-hidden="true"></i>
            <location-renderer :locationString="growthSession.location"/>
        </div>

        <div v-if="growthSession.anydesk && user" class="text-gray-700 text-left mb-4 break-all">
            <i class="fa fa-desktop text-lg mr-1" aria-hidden="true"></i>
            {{ growthSession.anydesk.name }}
        </div>

        <div class="grid gap-2 mb-2">
            <v-button
                class="join-button"
                color="blue"
                @click="joinGrowthSession"
                text="Join Mob"
                v-show="growthSession.canJoin(user)"/>
            <v-button
                class="watch-button"
                color="orange"
                @click="watchGrowthSession"
                text="Spectate"
                v-show="growthSession.canWatch(user)"/>
            <v-button
                class="leave-button"
                color="red"
                @click="leaveGrowthSession"
                v-show="growthSession.canLeave(user)"
                text="Leave"/>
        </div>

        <div
            v-if="growthSession.title || isDraggable"
            class="flex items-center px-1 py-1 -mx-2 bg-slate-100 rounded-lg"
        >
                <a
                    aria-label="add-to-calendar"
                    :href="growthSession.calendarUrl"
                    target="_blank"
                    class="text-slate-600 group relative leading-none hover:bg-slate-300 hover:text-slate-900 rounded-lg h-10 w-10 inline-flex justify-center items-center"
                >
                    <i aria-hidden="true" class="fa fa-calendar"></i>
                    <div class="bg-slate-700 text-slate-50 hidden group-hover:block absolute p-2 bottom-full rounded-lg">Add to Calendar</div>
                </a>
                <button
                    v-show="growthSession.canEditOrDelete(user)"
                    class="update-button text-slate-600 leading-none hover:bg-slate-300 hover:text-slate-900 rounded-lg h-10 w-10 inline-flex justify-center items-center"
                    @click.prevent="emit('edit-requested', growthSession)">
                    <i aria-hidden="true" class="fa fa-edit"></i>
                </button>
                <button
                    v-show="user && user.is_vehikl_member"
                    class="copy-button text-slate-600 leading-none hover:bg-slate-300 hover:text-slate-900 rounded-lg h-10 w-10 inline-flex justify-center items-center"
                    @click.prevent="emit('copy-requested', growthSession)">
                    <i aria-hidden="true" class="fa fa-copy"></i>
                </button>
                <button
                    v-show="growthSession.canEditOrDelete(user)"
                    class="delete-button text-slate-600 leading-none hover:bg-slate-300 hover:text-slate-900 rounded-lg h-10 w-10 inline-flex justify-center items-center"
                    @click.prevent="onDeleteClicked">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </button>

                <div v-if="isDraggable"
                     @click.stop
                     class="z-10 handle h-10 w-10 hover:bg-slate-300 hover:text-slate-900 rounded-lg inline-flex justify-center items-center cursor-move">
                    <icon-draggable class="h-4 text-slate-600 hover:text-slate-800"/>
                </div>

            </div>
    </a>
</template>


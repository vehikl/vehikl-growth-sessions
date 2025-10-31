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
    <a class="group block px-6 py-5 mx-2 cursor-pointer overflow-visible mb-4 transition-smooth hover-lift bg-white rounded-xl border border-neutral-200 hover:border-neutral-300 hover:shadow-lg"
       :class="growthSession.is_public ? '' : 'ring-2 ring-vehikl-orange/20 border-vehikl-orange/30'"
       :href="growthSessionUrl">

        <div class="flex items-center justify-between flex-1 mb-3">
            <p class="text-sm font-semibold tracking-wide text-neutral-600 uppercase" v-text="growthSession.owner.name"/>
            <v-avatar :alt="`${growthSession.owner.name}'s Avatar`" :size="6" :src="growthSession.owner.avatar"/>
        </div>
       <h3 class="font-bold text-2xl text-vehikl-dark text-left mb-4 break-words group-hover:text-vehikl-orange transition-smooth"
        v-text="growthSession.title"/>


        <div class="flex justify-between items-center mb-4 px-3 py-2 bg-neutral-50 rounded-lg text-neutral-700">
            <div class="font-semibold text-sm">
                {{ growthSession.startTime }} to {{ growthSession.endTime }}
            </div>
            <div class="flex items-center gap-1.5 attendees-count">
                <i class="fa fa-user-circle text-base" aria-hidden="true"></i>
                <span class="font-semibold" v-text="growthSession.attendees.length"/>
                <span v-if="growthSession.attendee_limit" class="text-neutral-500 attendee-limit">/ {{ growthSession.attendee_limit }}</span>
            </div>
        </div>

        <GrowthSessionTags class="mb-4" :tags="growthSession.tags" />

        <pre
            class="mb-4 topic inline-block text-left break-words-fixed whitespace-pre-wrap max-h-48 overflow-y-auto overflow-x-hidden font-sans text-neutral-700 text-sm leading-relaxed"
            v-text="growthSession.topic"/>

        <div class="text-neutral-600 text-left mb-3 break-all flex items-center gap-2 text-sm">
            <i class="fa fa-compass text-base text-neutral-400" aria-hidden="true"></i>
            <location-renderer :locationString="growthSession.location"/>
        </div>

        <div v-if="growthSession.anydesk && user" class="text-neutral-600 text-left mb-4 break-all flex items-center gap-2 text-sm">
            <i class="fa fa-desktop text-base text-neutral-400" aria-hidden="true"></i>
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
            class="flex items-center gap-1 pt-3 mt-3 border-t border-neutral-100"
        >
                <a
                    aria-label="add-to-calendar"
                    :href="growthSession.calendarUrl"
                    target="_blank"
                    class="text-neutral-500 group/icon relative leading-none hover:bg-neutral-100 hover:text-vehikl-orange transition-smooth rounded-lg h-9 w-9 inline-flex justify-center items-center"
                >
                    <i aria-hidden="true" class="fa fa-calendar"></i>
                    <div class="bg-vehikl-dark text-white hidden group-hover/icon:block absolute px-3 py-1.5 bottom-full mb-2 rounded-lg text-xs whitespace-nowrap shadow-lg">Add to Calendar</div>
                </a>
                <button
                    v-show="growthSession.canEditOrDelete(user)"
                    class="update-button text-neutral-500 leading-none hover:bg-neutral-100 hover:text-vehikl-orange transition-smooth rounded-lg h-9 w-9 inline-flex justify-center items-center"
                    @click.prevent="emit('edit-requested', growthSession)">
                    <i aria-hidden="true" class="fa fa-edit"></i>
                </button>
                <button
                    v-show="user && user.is_vehikl_member"
                    class="copy-button text-neutral-500 leading-none hover:bg-neutral-100 hover:text-vehikl-orange transition-smooth rounded-lg h-9 w-9 inline-flex justify-center items-center"
                    @click.prevent="emit('copy-requested', growthSession)">
                    <i aria-hidden="true" class="fa fa-copy"></i>
                </button>
                <button
                    v-show="growthSession.canEditOrDelete(user)"
                    class="delete-button text-neutral-500 leading-none hover:bg-red-50 hover:text-red-600 transition-smooth rounded-lg h-9 w-9 inline-flex justify-center items-center"
                    @click.prevent="onDeleteClicked">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </button>

                <div v-if="isDraggable"
                     @click.stop
                     class="z-10 handle h-9 w-9 hover:bg-neutral-100 hover:text-vehikl-orange transition-smooth rounded-lg inline-flex justify-center items-center cursor-move ml-auto">
                    <icon-draggable class="h-4 text-neutral-500"/>
                </div>

            </div>
    </a>
</template>


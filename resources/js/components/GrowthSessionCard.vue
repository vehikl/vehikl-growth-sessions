<template>
    <a class="block bg-gray-100 border py-4 px-6 shadow rounded-lg hover:bg-blue-100 cursor-pointer"
       :class="growthSession.is_public ? 'border-blue-300' : 'border-vehikl-orange'"
         :href="growthSessionUrl">
        <div class="flex justify-end items-center" :class="{'mb-2': growthSession.title}" v-if="growthSession.title || isDraggable">
            <a
                :href="growthSession.calendarUrl"
                target="_blank"
                ref="add-to-calendar"
                class="text-blue-700 leading-none hover:bg-blue-200 rounded-full h-10 w-10 inline-flex justify-center items-center"
            >
                <i aria-hidden="true" class="fa fa-calendar"></i>
            </a>
            <button
                v-show="growthSession.canEditOrDelete(user)"
                class="update-button text-blue-700 leading-none hover:bg-blue-200 rounded-full h-10 w-10 inline-flex justify-center items-center"
                @click.prevent="$emit('edit-requested', growthSession)">
                <i aria-hidden="true" class="fa fa-edit"></i>
            </button>
            <button
                v-show="user && user.is_vehikl_member"
                class="copy-button text-blue-700 leading-none hover:bg-blue-200 rounded-full h-10 w-10 inline-flex justify-center items-center"
                @click.prevent="$emit('copy-requested', growthSession)">
                <i aria-hidden="true" class="fa fa-copy"></i>
            </button>
            <button
                v-show="growthSession.canEditOrDelete(user)"
                class="delete-button text-blue-700 leading-none hover:bg-blue-200 rounded-full h-10 w-10 inline-flex justify-center items-center"
                @click.prevent="onDeleteClicked">
                <i class="fa fa-trash" aria-hidden="true"></i>
            </button>

            <div v-if="isDraggable"
                 @click.stop
                 class="z-10 handle h-10 w-10 hover:bg-blue-200 rounded-full inline-flex justify-center items-center cursor-move">
                <icon-draggable class="h-4 text-blue-600 hover:text-blue-800"/>
            </div>

        </div>
        <h3 class="font-light text-lg text-blue-700 text-left mb-3"
            :class="{'pr-4': isDraggable}"
            ref="growth-session-title"
            v-text="growthSession.title"/>
        <pre
            class="mb-4 inline-block text-left break-words-fixed whitespace-pre-wrap max-h-64 overflow-y-auto overflow-x-hidden font-sans"
            ref="growth-session-topic"
            v-text="growthSession.topic"
            v-show="!growthSession.hasAlreadyHappened"/>
        <div class="flex items-center flex-1 mb-4 text-blue-700">
            <p class="mr-3">Host:</p>
            <p class="mr-6 text-md" v-text="growthSession.owner.name"/>
            <v-avatar :src="growthSession.owner.avatar" :alt="`${growthSession.owner.name}'s Avatar`" size="6"/>
        </div>
        <div class="flex justify-between mb-2 text-blue-700">
            <div class="flex items-center attendees-count">
                <i class="fa fa-user-circle text-lg mr-2" aria-hidden="true"></i>
                <span v-text="growthSession.attendees.length"/>
                <span v-if="growthSession.attendee_limit" class="pl-1 attendee-limit">of {{ growthSession.attendee_limit }}</span>
            </div>
            <div class="flex items-center">
                <i class="fa fa-clock-o text-lg mr-2" aria-hidden="true"></i>
                {{growthSession.startTime}} to {{growthSession.endTime}}
            </div>
        </div>

        <div class="text-blue-700 text-left mb-2 break-all">
            <i class="fa fa-compass text-xl mr-1" aria-hidden="true"></i>
            <location-renderer :locationString="growthSession.location"/>
        </div>

        <div v-if="growthSession.anydesk && user" class="text-blue-700 text-left mb-4 break-all">
            <i class="fa fa-desktop text-lg mr-1" aria-hidden="true"></i>
            {{ growthSession.anydesk.name }}
        </div>

        <div class="flex flex-row">
            <v-button
                class="join-button"
                color="blue"
                @click="joinGrowthSession"
                text="Join Mob"
                v-show="growthSession.canJoin(user)" />
            <v-button
                class="watch-button"
                color="orange"
                @click="watchGrowthSession"
                text="Spectate"
                v-show="growthSession.canWatch(user)" />
            <v-button
                class="leave-button"
                color="red"
                @click="leaveGrowthSession"
                v-show="growthSession.canLeave(user)"
                text="Leave" />
        </div>
    </a>
</template>

<script lang="ts">
import { Component, Prop, Vue } from 'vue-property-decorator';
import { GrowthSessionApi } from '../services/GrowthSessionApi';
import { GrowthSession } from '../classes/GrowthSession';
import { IUser } from '../types';
import VAvatar from './VAvatar.vue';
import VButton from './VButton.vue';
import IconDraggable from '../svgs/IconDraggable.vue';
import LocationRenderer from './LocationRenderer.vue';

@Component({
    components: {VAvatar, IconDraggable, LocationRenderer, VButton}
})
export default class GrowthSessionCard extends Vue {
    @Prop({required: true}) growthSession!: GrowthSession;
    @Prop({required: false, default: null}) user!: IUser;

    get isDraggable(): boolean {
        return this.growthSession.canEditOrDelete(this.user);
    }

    get growthSessionUrl() {
        return GrowthSessionApi.showUrl(this.growthSession);
    }

    async watchGrowthSession() {
        await this.growthSession.watch();
        this.$emit('growth-session-updated');
    }

    async joinGrowthSession() {
        await this.growthSession.join();
        this.$emit('growth-session-updated');
    }

    async leaveGrowthSession() {
        await this.growthSession.leave();
        this.$emit('growth-session-updated');
    }

    async onDeleteClicked() {
        if (confirm('Are you sure you want to delete?')) {
            await this.growthSession.delete();
            this.$emit('delete-requested', this.growthSession);
        }
    }
}
</script>


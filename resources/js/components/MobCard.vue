<template>
    <div class="bg-gray-100 border border-blue-300 shadow p-6 rounded-lg">
        <div class="flex items-center">
            <div class="w-12 h-12 relative">
                <div class="group w-full h-full rounded-full overflow-hidden shadow-inner">
                    <img :src="socialMob.owner.avatar" :alt="`${socialMob.owner.name}'s Avatar`"
                         class="object-cover object-center w-full h-full visible group-hover:hidden"/>
                </div>
            </div>
            <h3 class="ml-6 text-lg" v-text="socialMob.owner.name"/>
        </div>
        <h4 class="my-4" v-text="socialMob.topic"/>
        <div class="flex justify-between mb-2 text-blue-700">
            <div class="flex items-center attendees-count">
                <i class="fa fa-user-circle text-lg mr-2" aria-hidden="true"></i>
                <span v-text="socialMob.attendees.length"/>
            </div>
            <div class="flex items-center">
                <i class="fa fa-clock-o text-lg mr-2" aria-hidden="true"></i>
                {{startTime}} to {{endTime}}
            </div>
        </div>

        <div class="text-blue-700 text-left mb-4">
            <i class="fa fa-compass text-xl mr-1" aria-hidden="true"></i>
            <a v-if="isUrl(socialMob.location)" class="location underline" :href="socialMob.location" target="_blank" v-text="socialMob.location"/>
            <span v-else class="location" v-text="socialMob.location"/>
        </div>

        <button class="join-button w-32 bg-blue-500 hover:bg-blue-700 focus:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                @click="joinMob"
                v-show="!isOwner && !isGuest && !isAttendee && ! hasMobAlreadyHappened">
            Join
        </button>
        <button class="leave-button w-32 bg-red-500 hover:bg-red-700 focus:bg-red-700  text-white font-bold py-2 px-4 rounded"
                @click="leaveMob"
                v-show="isAttendee && ! hasMobAlreadyHappened">
            Leave
        </button>
        <div  v-show="isOwner && ! hasMobAlreadyHappened">
            <button class="update-button w-32 bg-orange-500 hover:bg-orange-700 focus:bg-orange-700 text-white font-bold py-2 px-4 rounded"
                    @click="$emit('edit-requested', socialMob)">
                Edit
            </button>
            <button class="delete-button w-16 bg-red-500 hover:bg-red-700 focus:bg-red-700 text-white font-bold py-2 px-4 rounded"
                    @click="onDeleteClicked">
                <i class="fa fa-trash" aria-hidden="true"></i>
            </button>
        </div>

    </div>

</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {ISocialMob, IUser} from '../types';
    import moment from 'moment';
    import axios from 'axios';
    import {SocialMobApi} from '../services/SocialMobApi';
    import {DateTimeApi} from '../services/DateTimeApi';

    @Component
    export default class MobCard extends Vue {
        @Prop({required: false}) user!: IUser;
        @Prop({required: true}) socialMob!: ISocialMob;

        async joinMob() {
            await SocialMobApi.join(this.socialMob);
            this.$emit('mob-updated');
        }

        async leaveMob() {
            await SocialMobApi.leave(this.socialMob);
            this.$emit('mob-updated');
        }

        async onDeleteClicked() {
            if (confirm('Are you sure you want to delete?')) {
                await SocialMobApi.delete(this.socialMob);
                this.$emit('delete-requested', this.socialMob);
            }
        }

        get startTime(): string {
            return DateTimeApi.parseByTime(this.socialMob.start_time).toTimeString12Hours(false);
        }

        get endTime(): string {
            return DateTimeApi.parseByTime(this.socialMob.end_time).toTimeString12Hours();
        }

        get isGuest(): boolean {
            return !this.user;
        }

        get isAttendee(): boolean {
            if (this.isGuest) {
                return false;
            }
            return this.socialMob.attendees.filter(user => user.id === this.user.id).length > 0;
        }

        get isOwner(): boolean {
            if (this.isGuest) {
                return false;
            }
            return this.socialMob.owner.id === this.user.id;
        }

        get hasMobAlreadyHappened(): boolean {
            return DateTimeApi.parseByDate(this.socialMob.date).isInAPastDate();
        }

        isUrl(possibleUrl: string): boolean {
            try {
                new URL(possibleUrl);
                return true;
            } catch {
                return false;
            }
        }
    }
</script>

<style lang="scss" scoped>
</style>

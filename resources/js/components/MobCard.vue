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
        <div class="flex justify-between">
            <div class="flex items-center attendees-count">
                <i class="fa fa-user-circle text-lg text-blue-700 mr-2" aria-hidden="true"></i>
                <span v-text="socialMob.attendees.length"/>
            </div>
            <div class="flex items-center">
                <i class="fa fa-clock-o text-lg text-blue-700 mr-2" aria-hidden="true"></i>
                <span v-text="timeDisplayed"/>
            </div>
            <button class="join-button bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    @click="joinMob"
                    v-show="!isUserAlreadyInTheMob && !isGuest">
                Join
            </button>
        </div>
    </div>

</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {ISocialMob, IUser} from '../types';
    import moment from 'moment';
    import axios from 'axios';

    @Component
    export default class MobCard extends Vue {
        @Prop({required: false, default: () => ({id: 0})}) user!: IUser;
        @Prop({required: true}) socialMob!: ISocialMob;

        async joinMob() {
            await axios.post(`/social_mob/${this.socialMob.id}/join`);
            this.$emit('joined-mob');
        }

        get timeDisplayed(): string {
            return moment(this.socialMob.start_time).format('LT');
        }

        get isGuest(): boolean {
            return this.user.id === 0;
        }

        get isUserAlreadyInTheMob(): boolean {
            let isOwner = this.socialMob.owner.id === this.user.id;
            let isAttendee = this.socialMob.attendees.filter(user => user.id === this.user.id).length > 0;

            return isOwner || isAttendee;
        }
    }
</script>

<style lang="scss" scoped>
</style>

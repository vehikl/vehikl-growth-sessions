<template>
    <form class="create-mob bg-white w-full p-4 text-left" @submit.prevent>
        <div class="mb-4 flex justify-between">
            <div>
                <label for="date" class="block text-gray-700 text-sm font-bold mb-2">
                    Date
                </label>
                <div class="border border-gray-400 p-1 w-48 flex justify-center">
                    <datepicker v-model="date" id="date"/>
                </div>
            </div>

            <button
                class="mt-6 w-48 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                :class="{'opacity-25 cursor-not-allowed': !isReadyToSubmit}"
                type="submit"
                :disabled="! isReadyToSubmit"
                @click="onSubmit"
                v-text="isCreating? 'Create' : 'Update'">
            </button>
        </div>

        <div class="mb-4 flex">
            <div>
                <label for="start_time" class="block text-gray-700 text-sm font-bold mb-2">
                    Start
                </label>
                <vue-timepicker v-model="startTime"
                                tabindex="-1"
                                hide-disabled-items
                                :hour-range="[['1p', '4p']]"
                                format="hh:mm a"
                                :minute-interval="15"
                                id="start_time"/>
            </div>
            <div class="ml-12">
                <label for="end_time" class="block text-gray-700 text-sm font-bold mb-2">
                    End
                </label>
                <vue-timepicker v-model="endTime"
                                tabindex="-1"
                                hide-disabled-items
                                :hour-range="[['2p', '5p']]"
                                format="hh:mm a"
                                :minute-interval="15"
                                id="end_time"/>
            </div>
        </div>

        <div class="mb-4">
            <label for="topic" class="block text-gray-700 text-sm font-bold mb-2">
                Topic
            </label>
            <textarea id="topic"
                      v-model="topic"
                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                      rows="4"
                      placeholder="What is this mob about?"/>
        </div>

        <div class="mb-4">
            <label for="location" class="block text-gray-700 text-sm font-bold mb-2">
                Location
            </label>
            <textarea id="location"
                      rows="2"
                      v-model="location"
                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                      placeholder="Where should people go to participate?"/>
        </div>
    </form>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {ISocialMob, IStoreSocialMobRequest, IUser} from '../types';
    import VueTimepicker from 'vue2-timepicker'
    import Datepicker from 'vuejs-datepicker';
    import {SocialMobApi} from '../services/SocialMobApi';
    import {DateTimeApi} from '../services/DateTimeApi';

    @Component({components: {VueTimepicker, Datepicker}})
    export default class CreateMob extends Vue {
        @Prop({required: true}) owner!: IUser;
        @Prop({required: false, default: null}) mob!: ISocialMob;
        @Prop({required: false, default: ''}) startDate!: string;

        startTime: string = '03:30 pm';
        endTime: string = '05:00 pm';
        location: string = '';
        topic: string = '';
        date: string = '';

        mounted() {
            this.date = this.startDate;
            let input = document.getElementById('topic');
            if (input) {
                input.focus();
            }

            if (this.mob) {
                this.date = DateTimeApi.parse(this.mob.start_time).toISOString();
                this.startTime = DateTimeApi.parse(this.mob.start_time).toTimeString12Hours();
                this.endTime = DateTimeApi.parse(this.mob.end_time).toTimeString12Hours();
                this.location = this.mob.location;
                this.topic = this.mob.topic;
            }
        }

        onSubmit() {
            if (this.isCreating) {
                return this.createMob();
            }
            this.updateMob();
        }

        async createMob() {
            try {
                let newMob: ISocialMob = await SocialMobApi.store(this.storeOrUpdatePayload);
                this.$emit('submitted', newMob);
            } catch (e) {
                alert('Something went wrong :(');
            }
        }

        async updateMob() {
            try {
                let updatedMob: ISocialMob = await SocialMobApi.update(this.mob, this.storeOrUpdatePayload);
                this.$emit('submitted', updatedMob);
            } catch (e) {
                alert('Something went wrong :(');
            }
        }

        get isCreating(): boolean {
            return !this.mob;
        }

        get dateString(): string {
            return DateTimeApi.parse(this.date).toDateString();
        }

        get isReadyToSubmit(): boolean {
            return !!this.startTime && !!this.endTime && !!this.date && !!this.location && !!this.topic;
        }

        get storeOrUpdatePayload(): IStoreSocialMobRequest {
            return {
                location: this.location,
                topic: this.topic,
                start_time: `${this.dateString} ${this.startTime}`,
                end_time: `${this.dateString} ${this.endTime}`
            }
        }
    }
</script>

<style lang="scss" scoped>
</style>

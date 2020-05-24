<template>
    <form class="create-mob bg-white w-full p-4 text-left" @submit.prevent>
        <div class="mb-4 flex justify-between">
            <div>
                <label for="date" class="block text-gray-700 text-sm font-bold mb-2">
                    Date
                </label>
                <div class="border border-gray-400 p-1">
                    <datepicker v-model="date" id="date"/>
                </div>
            </div>
            <div>
                <label for="time" class="block text-gray-700 text-sm font-bold mb-2">
                    Time
                </label>
                <vue-timepicker v-model="time"
                                tabindex="-1"
                                hide-disabled-items
                                :hour-range="[['1p', '5p']]"
                                format="hh:mm a"
                                :minute-interval="15"
                                id="time"/>
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

        <button
            class="mt-6 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
            :class="{'opacity-25 cursor-not-allowed': !isReadyToSubmit}"
            type="submit"
            :disabled="! isReadyToSubmit"
            @click="onSubmit"
            v-text="isCreating? 'Create' : 'Update'">
        </button>
    </form>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {ISocialMob, IStoreSocialMobRequest, IUpdateSocialMobRequest, IUser} from '../types';
    import VueTimepicker from 'vue2-timepicker'
    import Datepicker from 'vuejs-datepicker';
    import axios from 'axios';
    import moment from 'moment';

    @Component({components: {VueTimepicker, Datepicker}})
    export default class CreateMob extends Vue {
        @Prop({required: true}) owner!: IUser;
        @Prop({required: false, default: null}) mob!: ISocialMob;
        @Prop({required: false, default: ''}) startDate!: string;

        time: string = '03:30 pm';
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
                this.date = moment(this.mob.start_time).toISOString();
                this.time = moment(this.mob.start_time).format('hh:mm a');
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
                let response = await axios.post<ISocialMob>('social_mob', this.storeOrUpdatePayload);

                this.$emit('submitted', response.data);
            } catch (e) {
                alert('Something went wrong :(');
            }
        }

        async updateMob() {
            try {
                let response = await axios.put<ISocialMob>(`social_mob/${this.mob.id}`, this.storeOrUpdatePayload);

                this.$emit('submitted', response.data);
            } catch (e) {
                alert('Something went wrong :(');
            }
        }

        get isCreating(): boolean {
            return !this.mob;
        }

        get dateString(): string {
            return moment(this.date).format('YYYY-MM-DD');
        }

        get startTime(): string {
            return `${this.dateString} ${this.time}`;
        }

        get isReadyToSubmit(): boolean {
            return !!this.time && !!this.date && !!this.location && !!this.topic;
        }

        get storeOrUpdatePayload(): IStoreSocialMobRequest | IUpdateSocialMobRequest {
            return {
                location: this.location,
                topic: this.topic,
                start_time: this.startTime
            }
        }
    }
</script>

<style lang="scss" scoped>
</style>

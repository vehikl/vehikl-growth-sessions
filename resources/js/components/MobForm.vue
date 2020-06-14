<template>
    <form class="create-mob bg-white w-full p-4 text-left" @submit.prevent>
        <div class="mb-4 flex justify-between">
            <div>
                <label for="date" class="block text-gray-700 text-sm font-bold mb-2">
                    Date
                </label>
                <div class="border p-1 border-gray-400 flex justify-center"
                     :class="{'error-outline': getError('date')}">
                    <datepicker v-model="date" id="date" tabindex="1"/>
                </div>
            </div>

            <button
                class="mt-6 w-48 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                :class="{'opacity-25 cursor-not-allowed': !isReadyToSubmit}"
                type="submit"
                tabindex="6"
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
                                :class="{'error-outline': getError('start_time')}"
                                tabindex="2"
                                advanced-keyboard
                                auto-scroll
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
                                :class="{'error-outline': getError('end_time')}"
                                tabindex="3"
                                advanced-keyboard
                                auto-scroll
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
                      tabindex="4"
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
                      tabindex="5"
                      v-model="location"
                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                      placeholder="Where should people go to participate?"/>
        </div>
    </form>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {ISocialMob, IStoreSocialMobRequest, IUser, IValidationError} from '../types';
    import VueTimepicker from 'vue2-timepicker'
    import Datepicker from 'vuejs-datepicker';
    import {SocialMobApi} from '../services/SocialMobApi';
    import {DateTime} from '../classes/DateTime';

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
        validationErrors: IValidationError | null = null;

        mounted() {
            this.date = this.startDate;
            let input = document.getElementById('topic');
            if (input) {
                input.focus();
            }

            if (this.mob) {
                this.date = this.mob.date;
                this.startTime = DateTime.parseByTime(this.mob.start_time).toTimeString12Hours();
                this.endTime = DateTime.parseByTime(this.mob.end_time).toTimeString12Hours();
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

        onRequestFailed(exception: any) {
            if (exception.response?.status === 422) {
                this.validationErrors = exception.response.data;
            } else {
                alert('Something went wrong :(');
            }
        }

        getError(field: string): string {
            let errors = this.validationErrors?.errors[field];
            return errors ? errors[0] : '';
        }

        async createMob() {
            try {
                let newMob: ISocialMob = await SocialMobApi.store(this.storeOrUpdatePayload);
                this.$emit('submitted', newMob);
            } catch (e) {
                this.onRequestFailed(e);
            }
        }

        async updateMob() {
            try {
                let updatedMob: ISocialMob = await SocialMobApi.update(this.mob, this.storeOrUpdatePayload);
                this.$emit('submitted', updatedMob);
            } catch (e) {
                this.onRequestFailed(e);
            }
        }

        get isCreating(): boolean {
            return !this.mob;
        }

        get dateString(): string {
            return DateTime.parseByDate(this.date).toDateString();
        }

        get isReadyToSubmit(): boolean {
            return !!this.startTime && !!this.endTime && !!this.date && !!this.location && !!this.topic;
        }

        get storeOrUpdatePayload(): IStoreSocialMobRequest {
            return {
                location: this.location,
                topic: this.topic,
                date: this.date,
                start_time: this.startTime,
                end_time: this.endTime
            }
        }
    }
</script>

<style lang="scss" scoped>
    .error-outline {
        outline: red solid 2px;
    }
</style>

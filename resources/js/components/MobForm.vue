<template>
    <form @submit.prevent class="create-mob bg-white w-full p-4 text-left">
        <div class="mb-4 flex justify-between">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="date">
                    Date
                </label>
                <div :class="{'error-outline': getError('date')}"
                     class="border p-1 border-gray-400 flex justify-center">
                    <date-picker id="date" tabIndex="1" v-model="date"/>
                </div>
            </div>

            <button
                :class="{'opacity-25 cursor-not-allowed': !isReadyToSubmit}"
                :disabled="! isReadyToSubmit"
                @click="onSubmit"
                class="mt-6 w-48 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                tabindex="6"
                type="submit"
                v-text="isCreating? 'Create' : 'Update'">
            </button>
        </div>

        <div class="mb-4 flex">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="start_time">
                    Start
                </label>
                <vue-timepicker :class="{'error-outline': getError('start_time')}"
                                :minute-interval="15"
                                advanced-keyboard
                                auto-scroll
                                format="hh:mm a"
                                hide-disabled-items
                                id="start_time"
                                tabindex="2"
                                v-model="startTime"/>
            </div>
            <div class="ml-12">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="end_time">
                    End
                </label>
                <vue-timepicker :class="{'error-outline': getError('end_time')}"
                                :minute-interval="15"
                                advanced-keyboard
                                auto-scroll
                                format="hh:mm a"
                                hide-disabled-items
                                id="end_time"
                                tabindex="3"
                                v-model="endTime"/>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                Title
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                   :class="{'error-outline': getError('title')}"
                   id="title"
                   maxlength="45"
                   placeholder="In a short sentence, what is this mob about?"
                   tabindex="4"
                   type="text"
                   v-model="title"/>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                <input type="checkbox" ref="no-limit" @click="toggleLimitlessMob"> No Limit
            </label>
        </div>

        <div class="mb-4" v-if="canSetAtendeeLimit">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                Limit
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                   :class="{'error-outline': getError('limit')}"
                   id="limit"
                   placeholder="Limit of participants"
                   tabindex="4"
                   min="4"
                   type="number"
                   ref="attendee-limit"
                   v-model.number="attendeeLimit"/>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="topic">
                Topic
            </label>
            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                      :class="{'error-outline': getError('topic')}"
                      id="topic"
                      placeholder="Do you want to provide more details about this mob?"
                      rows="4"
                      tabindex="5"
                      v-model="topic"/>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                Location
            </label>
            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                      :class="{'error-outline': getError('location')}"
                      id="location"
                      placeholder="Where should people go to participate?"
                      rows="2"
                      tabindex="6"
                      v-model="location"/>
        </div>
    </form>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {ISocialMob, IStoreSocialMobRequest, IUser, IValidationError} from '../types';
    import VueTimepicker from 'vue2-timepicker'
    import {SocialMobApi} from '../services/SocialMobApi';
    import {DateTime} from '../classes/DateTime';
    import DatePicker from './DatePicker.vue';

    @Component({components: {DatePicker, VueTimepicker}})
    export default class CreateMob extends Vue {
        @Prop({required: true}) owner!: IUser;
        @Prop({required: false, default: null}) mob!: ISocialMob;
        @Prop({required: false, default: ''}) startDate!: string;

        startTime: string = '03:30 pm';
        endTime: string = '05:00 pm';
        location: string = '';
        title: string = '';
        attendeeLimit: number = 4;
        topic: string = '';
        date: string = '';
        validationErrors: IValidationError | null = null;
        canSetAtendeeLimit: boolean = true;

        mounted() {
            this.date = this.startDate;
            let input = document.getElementById('title');
            if (input) {
                input.focus();
            }

            if (this.mob) {
                this.date = this.mob.date;
                this.startTime = DateTime.parseByTime(this.mob.start_time).toTimeString12Hours();
                this.endTime = DateTime.parseByTime(this.mob.end_time).toTimeString12Hours();
                this.location = this.mob.location;
                this.title = this.mob.title;
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

        toggleLimitlessMob() {
            this.canSetAtendeeLimit = !this.canSetAtendeeLimit
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

        get isReadyToSubmit(): boolean {
            return !!this.startTime && !!this.endTime && !!this.date && !!this.location && !!this.topic && !!this.title;
        }

        get storeOrUpdatePayload(): IStoreSocialMobRequest {
            return {
                location: this.location,
                topic: this.topic,
                title: this.title,
                date: this.date,
                start_time: this.startTime,
                end_time: this.endTime,
                attendee_limit:  this.canSetAtendeeLimit ? this.attendeeLimit : undefined
            }
        }
    }
</script>

<style lang="scss" scoped>
    .error-outline {
        outline: red solid 2px;
    }
</style>

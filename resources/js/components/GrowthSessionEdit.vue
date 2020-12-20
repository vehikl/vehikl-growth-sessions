<template>
    <div class="max-w-5xl text-blue-600">
        <div class="mb-8 flex flex-col lg:flex-row lg:justify-between items-center">
            <h2 class="text-2xl lg:text-3xl font-sans font-light flex flex-1 items-center text-blue-700 pr-6">
                <v-avatar :alt="`${mob.owner.name}'s Avatar`" :src="mob.owner.avatar" class="mr-4"/>
                <input class="flex-1 shadow appearance-none border rounded w-full px-3" placeholder="Please enter a mob title"
                       type="text"
                       maxlength="45"
                       v-model="mob.title">
            </h2>
            <div>
                <button
                    @click.stop="updateMob"
                    class="update-button w-32 bg-orange-500 hover:bg-orange-700 focus:bg-orange-700 text-white font-bold py-2 px-4 rounded">
                    Save
                </button>
                <button
                    @click.stop="deleteMob"
                    class="delete-button w-16 bg-red-500 hover:bg-red-700 focus:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    <i aria-hidden="true" class="fa fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="flex flex-col lg:flex-row flex-wrap">
            <div class="flex-1 mr-2 max-w-5xl">
                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Topic</h3>
                <textarea :class="{'error-outline': getError('topic')}"
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                          id="topic"
                          placeholder="What is this mob about?"
                          rows="4"
                          v-model="mob.topic"/>
            </div>
            <div class="flex-none max-w-md">
                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Location:</h3>
                    <textarea :class="{'error-outline': getError('location')}"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                              id="location"
                              placeholder="Where should people go to participate?"
                              rows="2"
                              v-model="mob.location"/>
                </div>

                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Time:</h3>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="date">
                            Date
                        </label>
                        <div :class="{'error-outline': getError('date')}"
                             class="border p-1 border-gray-400 justify-center">
                            <date-picker id="date" v-model="mob.date"/>
                        </div>

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
                                        v-model="mob.start_time"/>
                    </div>
                    <div>
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
                                        v-model="mob.end_time"/>
                    </div>
                </div>

                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Attendees</h3>
                <ul>
                    <li class="flex items-center ml-6 my-4" v-for="attendee in mob.attendees">
                        <v-avatar :alt="`${attendee.name}'s Avatar`" :src="attendee.avatar" class="mr-3" size="12"/>
                        {{attendee.name}}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import {Component, Prop, Vue} from 'vue-property-decorator';
import {IGrowthSession, IUser, IValidationError} from '../types';
import {DateTime} from '../classes/DateTime';
import {GrowthSession} from '../classes/GrowthSession';
import VueTimepicker from 'vue2-timepicker';
import {GrowthSessionApi} from '../services/SocialMobApi';
import VAvatar from './VAvatar.vue';
import DatePicker from './DatePicker.vue';

@Component({components: {DatePicker, VAvatar, VueTimepicker}})
    export default class MobView extends Vue {
        @Prop({required: false}) user!: IUser;
        @Prop({required: true}) mobJson!: IGrowthSession;
        mob: GrowthSession = new GrowthSession(this.mobJson);
        validationErrors: IValidationError | null = null;

        get date(): string {
            return `${DateTime.parseByDate(this.mob.date).format('MMM-DD')}`
        }

        get time(): string {
            return `${this.mob.startTime} - ${this.mob.endTime}`;
        }

        async deleteMob() {
            await this.mob.delete();
            window.location.assign('/');
        }

        async updateMob() {
            try {
                const payload = {...this.mob}
                if (this.mob.isLimitless) {
                    payload.attendee_limit = null;
                }
                let updatedMob: IGrowthSession = await GrowthSessionApi.update(this.mob, payload);
                this.$emit('submitted', updatedMob);
                window.history.back()
            } catch (e) {
                this.onRequestFailed(e);
            }
        }

        onRequestFailed(exception: any) {
            if (exception.response?.status === 422) {
                console.log(exception.response.data.errors);
                this.validationErrors = exception.response.data;
            } else {
                alert('Something went wrong :(');
            }
        }

        getError(field: string): string {
            let errors = this.validationErrors?.errors[field];
            return errors ? errors[0] : '';
        }
    }
</script>

<style lang="scss" scoped>
    .error-outline {
        outline: red solid 2px;
    }
</style>

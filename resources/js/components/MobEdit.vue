<template>
    <div class="max-w-5xl text-blue-600">
        <div class="mb-8 flex flex-col lg:flex-row lg:justify-between items-center">
            <h2 class="text-2xl lg:text-3xl font-sans font-light flex flex-1 items-center text-blue-700 pr-6">
                <v-avatar class="mr-4" :src="mob.owner.avatar" :alt="`${mob.owner.name}'s Avatar`"/>
                <input type="text" class="flex-1 shadow appearance-none border rounded w-full px-3" v-model="mob.title">
            </h2>
            <div>
                <button
                    class="join-button w-32 bg-blue-500 hover:bg-blue-700 focus:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="mob.join()"
                    v-show="mob.canJoin(user)">
                    Join
                </button>
                <button
                    class="leave-button w-32 bg-red-500 hover:bg-red-700 focus:bg-red-700  text-white font-bold py-2 px-4 rounded"
                    @click.stop="mob.leave()"
                    v-show="mob.canLeave(user)">
                    Leave
                </button>
                <button
                    class="update-button w-32 bg-orange-500 hover:bg-orange-700 focus:bg-orange-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="updateMob"
                    v-if="mob.canEditOrDelete(user)">
                    Save
                </button>
                <button
                    class="delete-button w-16 bg-red-500 hover:bg-red-700 focus:bg-red-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="deleteMob"
                    v-if="mob.canEditOrDelete(user)">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="flex flex-col lg:flex-row flex-wrap">
            <div class="flex-1 mr-2 max-w-5xl">
                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Topic</h3>
                <textarea id="topic"
                          v-model="mob.topic"
                          :class="{'error-outline': getError('topic')}"
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                          rows="4"
                          placeholder="What is this mob about?"/>
            </div>
            <div class="flex-none max-w-md">
                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Location:</h3>
                    <textarea id="location"
                              rows="2"
                              :class="{'error-outline': getError('location')}"
                              v-model="mob.location"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                              placeholder="Where should people go to participate?"/>
                </div>

                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Time:</h3>
                    <div>
                        <label for="date" class="block text-gray-700 text-sm font-bold mb-2">
                            Date
                        </label>
                        <div class="border p-1 border-gray-400 justify-center"
                             :class="{'error-outline': getError('date')}">
                            <date-picker v-model="mob.date" id="date"/>
                        </div>

                        <label for="start_time" class="block text-gray-700 text-sm font-bold mb-2">
                            Start
                        </label>
                        <vue-timepicker v-model="mob.start_time"
                                        :class="{'error-outline': getError('start_time')}"
                                        advanced-keyboard
                                        auto-scroll
                                        hide-disabled-items
                                        format="hh:mm a"
                                        :minute-interval="15"
                                        id="start_time"/>
                    </div>
                    <div>
                        <label for="end_time" class="block text-gray-700 text-sm font-bold mb-2">
                            End
                        </label>
                        <vue-timepicker v-model="mob.end_time"
                                        :class="{'error-outline': getError('end_time')}"
                                        advanced-keyboard
                                        auto-scroll
                                        hide-disabled-items
                                        format="hh:mm a"
                                        :minute-interval="15"
                                        id="end_time"/>
                    </div>
                </div>

                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Attendees</h3>
                <ul>
                    <li v-for="attendee in mob.attendees" class="flex items-center ml-6 my-4">
                        <v-avatar class="mr-3" size="12" :src="attendee.avatar" :alt="`${attendee.name}'s Avatar`"/>
                        {{attendee.name}}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {ISocialMob, IUser, IValidationError} from '../types';
    import {DateTime} from '../classes/DateTime';
    import {SocialMob} from '../classes/SocialMob';
    import VueTimepicker from 'vue2-timepicker';
    import {SocialMobApi} from '../services/SocialMobApi';
    import VAvatar from './VAvatar.vue';
    import DatePicker from './DatePicker.vue';

    @Component({components: {DatePicker, VAvatar, VueTimepicker}})
    export default class MobView extends Vue {
        @Prop({required: false}) user!: IUser;
        @Prop({required: true}) mobJson!: ISocialMob;
        mob: SocialMob = new SocialMob(this.mobJson);
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
                let updatedMob: ISocialMob = await SocialMobApi.update(this.mob, this.mob);
                this.$emit('submitted', updatedMob);
                window.history.back()
            } catch (e) {
                this.onRequestFailed(e);
            }
        }

        onRequestFailed(exception: any) {
            if (exception.response?.status === 422) {
                console.log(exception.response.data.errors)
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

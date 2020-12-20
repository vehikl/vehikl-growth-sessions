<template>
    <div class="max-w-5xl text-blue-600">
        <div class="mb-8 flex flex-col lg:flex-row lg:justify-between items-center">
            <h2 class="text-2xl lg:text-3xl font-sans font-light flex items-center text-blue-700">
                <a :href="mob.owner.githubURL" ref="owner-avatar-link">
                    <v-avatar class="mr-4" :src="mob.owner.avatar" :alt="`${mob.owner.name}'s Avatar`"/>
                </a>
                {{mob.title}}
            </h2>
            <div>
                <button
                    class="join-button w-32 bg-blue-500 hover:bg-blue-700 focus:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="mob.join()"
                    v-show="mob.canJoin(userJson)">
                    Join
                </button>
                <button
                    class="leave-button w-32 bg-red-500 hover:bg-red-700 focus:bg-red-700  text-white font-bold py-2 px-4 rounded"
                    @click.stop="mob.leave()"
                    v-show="mob.canLeave(userJson)">
                    Leave
                </button>
                <button
                    class="update-button w-32 bg-orange-500 hover:bg-orange-700 focus:bg-orange-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="editMob"
                    v-if="mob.canEditOrDelete(userJson)">
                    Edit
                </button>
                <button
                    class="delete-button w-16 bg-red-500 hover:bg-red-700 focus:bg-red-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="deleteMob"
                    v-if="mob.canEditOrDelete(userJson)">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="flex flex-col lg:flex-row flex-wrap">
            <div class="flex-1 mr-2 max-w-5xl">
                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Topic</h3>
                <pre class="font-sans m-5 break-words-fixed whitespace-pre-wrap" v-text="mob.topic"/>
            </div>
            <div class="flex-none max-w-md">
                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Location:</h3>
                    <location-renderer :location-string="mob.location"/>
                </div>

                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Time:</h3>
                    <span class="mr-2">{{date}}</span>
                    <span class="whitespace-no-wrap">( {{time}} )</span>
                </div>

                <div class="mb-3" v-if="mob.attendee_limit">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Attendee Limit:</h3>
                    <span class="attendee_limit">{{ mob.attendee_limit }}</span>
                </div>

                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Attendees</h3>
                <ul>
                    <li v-for="attendee in mob.attendees">
                        <a ref="attendee" :href="attendee.githubURL" class="flex items-center ml-6 my-4">
                            <v-avatar size="12" class="mr-3" :src="attendee.avatar" :alt="`${attendee.name}'s Avatar`"/>
                            {{attendee.name}}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <comment-list class="mt-24 max-w-xl" :social-mob="mob" :user="userJson"/>
    </div>
</template>

<script lang="ts">
import {Component, Prop, Vue} from 'vue-property-decorator';
import {IGrowthSession, IUser} from '../types';
import {DateTime} from '../classes/DateTime';
import {GrowthSession} from '../classes/GrowthSession';
import VueTimepicker from 'vue2-timepicker';
import Datepicker from 'vuejs-datepicker';
import {GrowthSessionApi} from '../services/GrowthSessionApi';
import CommentList from './CommentList.vue';
import VAvatar from './VAvatar.vue';
import LocationRenderer from './LocationRenderer.vue';

@Component({components: {LocationRenderer, VAvatar, CommentList, VueTimepicker, Datepicker}})
    export default class MobView extends Vue {
        @Prop({required: false}) userJson!: IUser;
        @Prop({required: true}) mobJson!: IGrowthSession;
        mob: GrowthSession = new GrowthSession(this.mobJson);

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

        async editMob() {
            window.location.assign(GrowthSessionApi.editUrl(this.mob));
        }
    }
</script>

<style lang="scss" scoped>
</style>
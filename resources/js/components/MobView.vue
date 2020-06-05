<template>
    <div class="max-w-5xl text-blue-600">
        <div class="mb-8 flex flex-col lg:flex-row lg:justify-between items-center">
            <h2 class="text-2xl lg:text-3xl font-sans font-light flex items-center text-blue-700">
                <div class="w-20 h-20 relative mr-4">
                    <div class="group w-full h-full rounded-full overflow-hidden shadow-inner">
                        <img :src="mob.owner.avatar" :alt="`${mob.owner.name}'s Avatar`"
                             class="object-cover object-center w-full h-full visible group-hover:hidden"/>
                    </div>
                </div>
                {{mobName}}
            </h2>
            <div>
                <button
                    class="join-button w-32 bg-blue-500 hover:bg-blue-700 focus:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="mob.join().then(() => $forceUpdate())"
                    v-show="mob.canJoin(user)">
                    Join
                </button>
                <button
                    class="leave-button w-32 bg-red-500 hover:bg-red-700 focus:bg-red-700  text-white font-bold py-2 px-4 rounded"
                    @click.stop="mob.leave().then(() => $forceUpdate())"
                    v-show="mob.canLeave(user)">
                    Leave
                </button>
                <button class="update-button w-32 bg-orange-500 hover:bg-orange-700 focus:bg-orange-700 text-white font-bold py-2 px-4 rounded"
                        @click.stop="editOrSave"
                        v-if="mob.canEditOrDelete(user)">
                    {{editModeOn ? 'Save' : 'Edit'}}
                </button>
                <button class="delete-button w-16 bg-red-500 hover:bg-red-700 focus:bg-red-700 text-white font-bold py-2 px-4 rounded"
                        @click.stop="deleteMob"
                        v-if="mob.canEditOrDelete(user)">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="flex flex-col lg:flex-row flex-wrap">
            <div class="flex-1 max-w-5xl">
                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Topic</h3>
                <pre class="font-sans m-5 whitespace-pre-wrap" v-text="mob.topic"/>
            </div>
            <div class="flex-none">
                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Location:</h3>
                    <a v-if="mob.isLocationAnUrl" class="underline" :href="mob.location" target="_blank"
                       v-text="mob.location"/>
                    <span v-else v-text="mob.location"></span>
                </div>

                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Time:</h3>
                    <span class="mr-2">{{date}}</span>
                    <span class="whitespace-no-wrap">( {{time}} )</span>
                </div>

                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Attendees</h3>
                <ul>
                    <li v-for="attendee in mob.attendees" class="flex items-center ml-6 my-4">
                        <div class="w-12 h-12 relative mr-3">
                            <div class="group w-full h-full rounded-full overflow-hidden shadow-inner">
                                <img :src="attendee.avatar" :alt="`${attendee.name}'s Avatar`"
                                     class="object-cover object-center w-full h-full visible group-hover:hidden"/>
                            </div>
                        </div>
                        {{attendee.name}}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {IUser, ISocialMob} from '../types';
    import {DateTime} from '../classes/DateTime';
    import {SocialMob} from '../classes/SocialMob';

    @Component
    export default class MobView extends Vue {
        @Prop({required: false}) user!: IUser;
        @Prop({required: true}) mobJson!: ISocialMob;
        mob: SocialMob = new SocialMob(this.mobJson);
        editModeOn: boolean = false;

        get mobName(): string {
            return `${this.mob.owner.name}'s ${DateTime.parseByDate(this.mob.date).weekDayString()} Mob`;
        }

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

        editOrSave() {
            this.editModeOn = !this.editModeOn;
        }
    }
</script>

<style lang="scss" scoped>
</style>

<template>
    <div class="max-w-5xl text-blue-600">
        <h2 class="text-2xl lg:text-3xl font-sans font-light mb-8 flex items-center text-blue-700">
            <div class="w-20 h-20 relative mr-4">
                <div class="group w-full h-full rounded-full overflow-hidden shadow-inner">
                    <img :src="mob.owner.avatar" :alt="`${mob.owner.name}'s Avatar`"
                         class="object-cover object-center w-full h-full visible group-hover:hidden"/>
                </div>
            </div>
            {{mobName}}
        </h2>

        <div class="flex flex-col lg:flex-row flex-wrap">
            <div class="flex-1 max-w-5xl">
                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Topic</h3>
                <pre class="font-sans m-5 whitespace-pre-wrap" v-text="mob.topic"/>
            </div>
            <div class="flex-none">
                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Location:</h3>
                    <a v-if="isUrl(mob.location)" class="underline" :href="mob.location" target="_blank" v-text="mob.location"/>
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
    import {ISocialMob} from '../types';
    import {DateTimeApi} from '../services/DateTimeApi';
    import {StringApi} from '../services/StringApi';

    @Component
    export default class MobView extends Vue {
        @Prop({required: true}) mob!: ISocialMob;

        get mobName(): string {
            return `${this.mob.owner.name}'s ${DateTimeApi.parseByDate(this.mob.date).weekDayString()} Mob`;
        }
        get date(): string {
            return `${DateTimeApi.parseByDate(this.mob.date).format('MMM-DD')}`
        }

        get time(): string {
            return `${this.mob.start_time} - ${this.mob.end_time}`;
        }

        isUrl(possibleUrl: string): boolean {
            return StringApi.isUrl(possibleUrl);
        }
    }
</script>

<style lang="scss" scoped>
</style>

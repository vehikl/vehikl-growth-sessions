<template>
    <div class="bg-purple-200 p-6 rounded-lg">
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
                <i class="fa fa-user-circle text-lg text-purple-700 mr-2" aria-hidden="true"></i>
                <span v-text="socialMob.attendees.length"/>
            </div>
            <div class="flex items-center">
                <i class="fa fa-clock-o text-lg text-purple-700 mr-2" aria-hidden="true"></i>
                <span v-text="timeDisplayed"/>
            </div>
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Join</button>
        </div>
    </div>

</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {ISocialMob} from '../types';
    import moment from 'moment';

    @Component
    export default class MobCard extends Vue {
        @Prop({required: true}) socialMob!: ISocialMob;

        get timeDisplayed(): string {
            return moment(this.socialMob.start_time).format('LT');
        }
    }
</script>

<style lang="scss" scoped>
</style>

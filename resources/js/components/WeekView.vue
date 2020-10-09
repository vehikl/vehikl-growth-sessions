<template>
    <div v-if="socialMobs.isReady">
        <div class="flex justify-center items-center text-xl text-blue-600 font-bold">
            <button class="load-previous-week mx-4 mb-2"
                    aria-label="Load previous week"
                    @click="changeReferenceDate(-7)">
                <i class="fa fa-chevron-left" aria-hidden="true"></i>
            </button>
            <h2 class="text-center mb-2 w-72" v-if="socialMobs.weekDates.length > 0">
                Week of
                {{ socialMobs.firstDay.format('MMM-DD')}} to
                {{ socialMobs.lastDay.format('MMM-DD')}}
            </h2>
            <button class="load-next-week mx-4 mb-2"
                    aria-label="Load next week"
                    @click="changeReferenceDate(+7)">
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>

        <div class="flex justify-center flex-wrap">
            <modal :dynamic="true" :width="500" :height="600" name="mob-form">
                <div class="flex w-full h-full">
                    <mob-form class="mob-form"
                              @submitted="onFormSubmitted"
                              :owner="user"
                              :mob="mobToUpdate"
                              :start-date="newMobDate"/>
                </div>
            </modal>
            <div class="day text-center mx-1 mb-2 px-2 md:block"
                 v-for="date in socialMobs.weekDates"
                 :weekDay="date.weekDayString()"
                 :key="date.toDateString()"
                 :class="{
                 'bg-blue-100': date.weekDayNumber() % 2 === 0,
                 'bg-blue-200': date.weekDayNumber() % 2 !== 0,
                 'hidden': !date.isToday()
                 }">
                <h3 class="text-lg text-blue-700 font-bold mt-6 mb-3" v-text="date.weekDayString()"></h3>
                <div v-show="socialMobs.getMobByDate(date).length === 0" class="text-blue-600 text-lg my-4">
                    <p v-text="`${Nothingator.random()}...`"/>
                    <p v-show="user && date.isToday()">Why don't you create the first one?</p>
                </div>

                <draggable :list="socialMobs.getMobByDate(date)"
                           group="social-mobs"
                           class="h-full w-full"
                           handle=".handle"
                           :date="date"
                           @end="onDragEnd"
                           @change="onChange">
                    <div v-for="socialMob in socialMobs.getMobByDate(date)"
                         :key="socialMob.id">
                        <mob-card
                            @mob-updated="getAllMobsOfTheWeek"
                            @edit-requested="onMobEditRequested"
                            @delete-requested="getAllMobsOfTheWeek"
                            :socialMob="socialMob"
                            :user="user"
                            class="mb-3 transform transition-transform duration-150"/>
                    </div>

                    <button
                        class="create-mob text-5xl h-20 w-20 text-blue-600 hover:text-blue-700 focus:text-blue-700 font-bold my-3"
                        @click="onCreateNewMobClicked(date)"
                        v-if="user && ! date.isInAPastDate()">
                        <i class="fa fa-plus-circle" aria-hidden="true"></i>
                    </button>
                </draggable>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {IUser} from '../types';
    import MobCard from './MobCard.vue';
    import MobForm from './MobForm.vue';
    import {SocialMobApi} from '../services/SocialMobApi';
    import {DateTime} from '../classes/DateTime';
    import Draggable from 'vuedraggable';
    import {SocialMob} from '../classes/SocialMob';
    import {WeekMobs} from '../classes/WeekMobs';
    import {Nothingator} from '../classes/Nothingator';

    interface IMobCardDragChange {
        added?: { element: SocialMob, index: number }
        removed?: { element: SocialMob, index: number }
    }

    @Component({
        components: {MobForm, MobCard, Draggable}
    })
    export default class WeekView extends Vue {
        @Prop({required: false, default: null}) user!: IUser;
        referenceDate: DateTime = DateTime.today();
        socialMobs: WeekMobs = WeekMobs.empty();
        newMobDate: string = '';
        mobToUpdate: SocialMob | null = null;
        DateTime = DateTime;
        Nothingator = Nothingator;
        draggedMob!: SocialMob;

        async created() {
            await this.getAllMobsOfTheWeek();
        }

        async onDragEnd(location: any) {
            let targetDate = location.to.__vue__.$attrs.date;
            try {
                await SocialMobApi.update(this.draggedMob, {date: targetDate.toDateString()});
            } catch (e) {
            }

            await this.getAllMobsOfTheWeek();
        }

        onChange(change: IMobCardDragChange) {
            if (change.added) {
                return this.draggedMob = change.added.element;
            }
        }

        async getAllMobsOfTheWeek() {
            this.socialMobs = await SocialMobApi.getAllMobsOfTheWeek(this.referenceDate.toDateString());
        }

        async onFormSubmitted() {
            await this.getAllMobsOfTheWeek();
            this.$modal.hide('mob-form');
        }

        onCreateNewMobClicked(startDate: DateTime) {
            this.mobToUpdate = null;
            this.newMobDate = startDate.toISOString();
            this.$modal.show('mob-form');
        }

        onMobEditRequested(mob: SocialMob) {
            this.mobToUpdate = mob;
            this.newMobDate = '';
            this.$modal.show('mob-form');
        }

        async changeReferenceDate(deltaDays: number) {
            this.referenceDate.addDays(deltaDays);
            await this.getAllMobsOfTheWeek();
        }
    }
</script>

<style lang="scss" scoped>
    .day {
        width: 20rem;
        min-height: 35rem;
    }
</style>

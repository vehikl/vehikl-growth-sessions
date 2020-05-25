<template>
    <div>
        <div class="flex justify-center items-center text-xl text-blue-600 font-bold">
            <button class="load-previous-week mx-4 mb-2"
                    aria-label="Load previous week"
                    @click="changeReferenceDate(-7)">
                <i class="fa fa-chevron-left" aria-hidden="true"></i>
            </button>
            <h2 class="text-center mb-2 w-72">
                Week of
                {{ DateTimeApi.parse(weekDates[0]).format('MMM-DD')}} to
                {{ DateTimeApi.parse(weekDates[4]).format('MMM-DD')}}
            </h2>
            <button class="load-next-week mx-4 mb-2"
                    aria-label="Load next week"
                    @click="changeReferenceDate(+7)">
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>

        <div class="flex justify-center flex-wrap">
            <modal :dynamic="true" :width="500" :height="450" name="mob-form">
                <div class="flex w-full h-full">
                    <mob-form class="mob-form"
                              @submitted="onFormSubmitted"
                              :owner="user"
                              :mob="mobToUpdate"
                              :start-date="newMobStartDate"/>
                </div>
            </modal>
            <div class="day text-center mx-1 mb-2 px-2 md:block"
                 :weekDay="date.weekDayString()"
                 v-for="date in weekDates"
                 :key="date.toString()"
                 :class="{
                 'bg-blue-100': date.weekDayNumber() % 2 === 0,
                 'bg-blue-200': date.weekDayNumber() % 2 !== 0,
                 'hidden': !date.isSame(DateTimeApi.today())
                 }">
                <h3 class="text-lg text-blue-700 font-bold mt-6 mb-3" v-text="date.weekDayString()"></h3>
                <div v-if="user">
                    <button
                        class="create-mob bg-green-600 hover:bg-green-700 focus:bg-green-700 text-white font-bold py-2 px-4 rounded mb-3"
                        @click="onCreateNewMobClicked(date)">
                        Create new mob
                    </button>
                </div>
                <draggable :list="socialMobs[date]"
                           group="social-mobs"
                           class="h-full w-full"
                           :date="date"
                           @end="onDragEnd"
                           @change="onChange">
                    <mob-card v-for="socialMob in socialMobs[date]"
                              :key="socialMob.id"
                              @mob-updated="getAllMobsOfTheWeek"
                              :user="user"
                              @edit-requested="onMobEditRequested"
                              @delete-requested="getAllMobsOfTheWeek"
                              :socialMob="socialMob"
                              class="my-3"/>
                </draggable>

            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {ISocialMob, IUser, IWeekMobs} from '../types';
    import MobCard from './MobCard.vue';
    import MobForm from './MobForm.vue';
    import {SocialMobApi} from '../services/SocialMobApi';
    import {DateTimeApi} from '../services/DateTimeApi';
    import Draggable from 'vuedraggable';

    interface IMobCardDragChange {
        added?: { element: ISocialMob, index: number }
        removed?: { element: ISocialMob, index: number }
    }

    @Component({
        components: {MobForm, MobCard, Draggable}
    })
    export default class WeekView extends Vue {
        @Prop({required: false, default: null}) user!: IUser;
        referenceDate: DateTimeApi = DateTimeApi.today();
        socialMobs: IWeekMobs = {};
        newMobStartDate: string = '';
        mobToUpdate: ISocialMob | null = null;
        DateTimeApi = DateTimeApi;
        draggedMob!: ISocialMob;

        async created() {
            await this.getAllMobsOfTheWeek();
        }

        async onDragEnd(location: any) {
            let targetDate = location.to.__vue__.$attrs.date;
            const userOwnsDraggedMob = this.draggedMob.owner.id === this.user.id;
            if (userOwnsDraggedMob && confirm(`Are you sure you want to move this mob to ${targetDate.format('MMM-DD')} (${targetDate.weekDayString()})?`)) {
                await SocialMobApi.update(this.draggedMob, {start_date: targetDate.toString()});
            }
            await this.getAllMobsOfTheWeek();
        }

        onChange(change: IMobCardDragChange) {
            if (change.added) {
                return this.draggedMob = change.added.element;
            }
        }

        async getAllMobsOfTheWeek() {
            this.socialMobs = await SocialMobApi.getAllMobsOfTheWeek(this.referenceDate.toString());
        }

        async onFormSubmitted() {
            await this.getAllMobsOfTheWeek();
            this.$modal.hide('mob-form');
        }

        onCreateNewMobClicked(startDate: DateTimeApi) {
            this.newMobStartDate = startDate.toISOString();
            this.$modal.show('mob-form');
        }

        onMobEditRequested(mob: ISocialMob) {
            this.mobToUpdate = mob;
            this.newMobStartDate = '';
            this.$modal.show('mob-form');
        }

        async changeReferenceDate(deltaDays: number) {
            this.referenceDate.addDays(deltaDays);
            await this.getAllMobsOfTheWeek();
        }

        get weekDates(): DateTimeApi[] {
            return Object.keys(this.socialMobs).map((date: string) => DateTimeApi.parse(date));
        }
    }
</script>

<style scoped>
    .day {
        width: 20rem;
        min-height: 35rem;
    }
</style>

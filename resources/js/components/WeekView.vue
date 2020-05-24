<template>
    <div>
        <div class="flex justify-center items-center text-xl text-blue-600 font-bold">
            <button class="load-previous-week mx-4" @click="changeReferenceDate(-7)"><i class="fa fa-chevron-left pb-3"
                                                                                        aria-hidden="true"></i></button>
            <h2 class="text-center mb-2">Week of
                {{ DateApi.parse(weekDates[0]).format('MMMM-DD')}} to
                {{ DateApi.parse(weekDates[4]).format('MMMM-DD')}}
            </h2>
            <button class="load-next-week mx-4" @click="changeReferenceDate(+7)"><i class="fa fa-chevron-right pb-3"
                                                                                    aria-hidden="true"></i></button>
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
                 :class="{
                 'bg-blue-100': date.weekDayNumber() % 2 === 0,
                 'bg-blue-200': date.weekDayNumber() % 2 !== 0,
                 'hidden': !date.isSame(DateApi.today())
                 }">
                <h3 class="text-lg text-blue-700 font-bold mt-6 mb-3" v-text="date.weekDayString()"></h3>
                <div v-if="user">
                    <button
                        class="create-mob bg-green-600 hover:bg-green-700 focus:bg-green-700 text-white font-bold py-2 px-4 rounded mb-3"
                        @click="onCreateNewMobClicked(date)">
                        Create new mob
                    </button>
                </div>

                <mob-card v-for="socialMob in socialMobs[date]"
                          @mob-updated="getAllMobsOfTheWeek"
                          :user="user"
                          @edit-requested="onMobEditRequested"
                          @delete-requested="getAllMobsOfTheWeek"
                          :key="socialMob.id"
                          :socialMob="socialMob"
                          class="my-3"/>
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
    import {DateApi} from '../services/DateApi';

    @Component({
        components: {MobForm, MobCard}
    })
    export default class WeekView extends Vue {
        @Prop({required: false, default: null}) user!: IUser;
        referenceDate: DateApi = DateApi.today();
        socialMobs: IWeekMobs = {};
        newMobStartDate: string = '';
        mobToUpdate: ISocialMob | null = null;
        DateApi = DateApi;

        async created() {
            await this.getAllMobsOfTheWeek();
        }

        async getAllMobsOfTheWeek() {
            this.socialMobs = await SocialMobApi.getAllMobsOfTheWeek(this.referenceDate.toString());
        }

        async onFormSubmitted() {
            await this.getAllMobsOfTheWeek();
            this.$modal.hide('mob-form');
        }

        onCreateNewMobClicked(startDate: DateApi) {
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

        get weekDates(): DateApi[] {
            return Object.keys(this.socialMobs).map((date: string) => DateApi.parse(date));
        }
    }
</script>

<style scoped>
    .day {
        width: 20rem;
        min-height: 35rem;
    }
</style>

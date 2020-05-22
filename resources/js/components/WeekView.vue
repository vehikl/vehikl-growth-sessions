<template>
    <div>
        <h2 class="text-center text-xl text-blue-600 font-bold mb-2">Week of
            {{ moment(weekDates[0]).format('MMMM-DD')}} to
            {{ moment(weekDates[4]).format('MMMM-DD')}}
        </h2>
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
                 v-for="date in weekDates"
                 :class="{
                 'bg-blue-100': moment(date).weekday() % 2 === 0,
                 'bg-blue-200': moment(date).weekday() % 2 !== 0,
                 'hidden': moment().format('MMMM-DD') !==  moment(date).format('MMMM-DD')
                 }">
                <h3 class="text-lg text-blue-700 font-bold mt-6 mb-3" v-text="moment(date).format('dddd')"></h3>
                <div v-if="user">
                    <button class="create-mob bg-green-600 hover:bg-green-700 focus:bg-green-700 text-white font-bold py-2 px-4 rounded mb-3"
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
    import axios from 'axios';
    import MobCard from './MobCard.vue';
    import moment from 'moment';
    import MobForm from './MobForm.vue';

    @Component({
        components: {MobForm, MobCard}
    })
    export default class WeekView extends Vue {
        @Prop({required: false, default: null}) user!: IUser;

        socialMobs: IWeekMobs = {};
        newMobStartDate: string = "";
        mobToUpdate: ISocialMob | null = null;
        moment = moment;

        created() {
            this.getAllMobsOfTheWeek();
        }

        async getAllMobsOfTheWeek() {
            const response = await axios.get<IWeekMobs>('/social_mob/week');
            this.socialMobs = response.data;
        }

        async onFormSubmitted() {
            await this.getAllMobsOfTheWeek();
            this.$modal.hide('mob-form');
        }

        onCreateNewMobClicked(startDate: string) {
            this.newMobStartDate = moment(startDate).toISOString();
            this.$modal.show('mob-form');
        }

        onMobEditRequested(mob: ISocialMob) {
            this.mobToUpdate = mob;
            this.newMobStartDate = "";
            this.$modal.show('mob-form');
        }

        get weekDates(): string[] {
            return Object.keys(this.socialMobs);
        }
    }
</script>

<style scoped>
    .day {
        width: 20rem;
        min-height: 35rem;
    }
</style>

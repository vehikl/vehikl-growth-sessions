<template>
    <div>
        <div class="flex">
            <div class="flex-1 text-center">
                <h3 class="text-lg font-semibold">Monday</h3>
                <div v-for="socialMob in socialMobs.monday"
                     :key="socialMob.id"
                     v-text="socialMob.topic"/>
            </div>
            <div class="flex-1 text-center">
                <h3 class="text-lg font-semibold">Tuesday</h3>
                <div v-for="socialMob in socialMobs.tuesday"
                     :key="socialMob.id"
                     v-text="socialMob.topic"/>
            </div>
            <div class="flex-1 text-center">
                <h3 class="text-lg font-semibold">Wednesday</h3>
                <div v-for="socialMob in socialMobs.wednesday"
                     :key="socialMob.id"
                     v-text="socialMob.topic"/>
            </div>
            <div class="flex-1 text-center">
                <h3 class="text-lg font-semibold">Thursday</h3>
                <div v-for="socialMob in socialMobs.thursday"
                     :key="socialMob.id"
                     v-text="socialMob.topic"/>
            </div>
            <div class="flex-1 text-center">
                <h3 class="text-lg font-semibold">Friday</h3>
                <div v-for="socialMob in socialMobs.friday"
                     :key="socialMob.id"
                     v-text="socialMob.topic"/>
            </div>
        </div>
    </div>

</template>

<script lang="ts">
    import {Component, Vue} from 'vue-property-decorator';
    import {IWeekMobs} from '../types';
    import axios from 'axios';

    const getEmptyWeekMobs = () => ({monday: [], tuesday: [], wednesday: [], thursday: [], friday: []});
    @Component
    export default class WeekView extends Vue {
        socialMobs: IWeekMobs = getEmptyWeekMobs();

        async created() {
            const response = await axios.get<IWeekMobs>('/social_mob/week');
            this.socialMobs = response.data;
        }
    }
</script>

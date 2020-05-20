<template>
    <div>
        <div class="flex mx-6">
            <div class="flex-1 text-center">
                <h3 class="text-lg font-semibold mt-6 mb-3">Monday</h3>
                <mob-card v-for="socialMob in socialMobs.monday"
                          :key="socialMob.id"
                          :socialMob="socialMob"
                          class="my-3"/>
            </div>
            <div class="flex-1 text-center">
                <h3 class="text-lg font-semibold mt-6 mb-3">Tuesday</h3>
                <mob-card v-for="socialMob in socialMobs.tuesday"
                          :key="socialMob.id"
                          :socialMob="socialMob"
                          class="my-3"/>
            </div>
            <div class="flex-1 text-center">
                <h3 class="text-lg font-semibold mt-6 mb-3">Wednesday</h3>
                <mob-card v-for="socialMob in socialMobs.wednesday"
                          :key="socialMob.id"
                          :socialMob="socialMob"
                          class="my-3"/>
            </div>
            <div class="flex-1 text-center">
                <h3 class="text-lg font-semibold mt-6 mb-3">Thursday</h3>
                <mob-card v-for="socialMob in socialMobs.thursday"
                          :key="socialMob.id"
                          :socialMob="socialMob"
                          class="my-3"/>
            </div>
            <div class="flex-1 text-center">
                <h3 class="text-lg font-semibold mt-6 mb-3">Friday</h3>
                <mob-card v-for="socialMob in socialMobs.friday"
                          :key="socialMob.id"
                          :socialMob="socialMob"
                          class="my-3"/>
            </div>
        </div>
    </div>

</template>

<script lang="ts">
    import {Component, Vue} from 'vue-property-decorator';
    import {IWeekMobs} from '../types';
    import axios from 'axios';
    import MobCard from './MobCard.vue';

    const getEmptyWeekMobs = () => ({monday: [], tuesday: [], wednesday: [], thursday: [], friday: []});
    @Component({
        components: {MobCard}
    })
    export default class WeekView extends Vue {
        socialMobs: IWeekMobs = getEmptyWeekMobs();

        async created() {
            const response = await axios.get<IWeekMobs>('/social_mob/week');
            this.socialMobs = response.data;
        }
    }
</script>

<template>
    <div>
        <div v-for="socialMob in socialMobs"
             :key="socialMob.id"
             v-text="socialMob.topic">
        </div>
    </div>
</template>

<script lang="ts">
    import {Component, Vue} from 'vue-property-decorator';
    import {ISocialMob} from '../types';
    import axios from 'axios';

    @Component
    export default class WeekView extends Vue {
        socialMobs: ISocialMob[] = [];

        async created() {
            const response = await axios.get<ISocialMob[]>('/social_mob?filter=week');
            this.socialMobs = response.data;
        }
    }
</script>

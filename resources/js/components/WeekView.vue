<template>
    <div>
        <div class="flex mx-6">
            <div class="flex-1 text-center" v-for="day in days">
                <h3 class="text-lg font-semibold mt-6 mb-3" v-text="moment(day).format('dddd')"></h3>
                <div v-if="user">
                    <button class="create-mob bg-green-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-3"
                            @click="$modal.show(`create-mob-${day}`)">
                        Create new mob
                    </button>
                    <modal :name="`create-mob-${day}`">
                        <create-mob class="create-mob" :owner="user"/>
                    </modal>
                </div>

                <mob-card v-for="socialMob in socialMobs[day]"
                          :key="socialMob.id"
                          :socialMob="socialMob"
                class="my-3"/>
            </div>
        </div>
    </div>

</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {IUser, IWeekMobs} from '../types';
    import axios from 'axios';
    import MobCard from './MobCard.vue';
    import CreateMob from './CreateMob.vue';
    import moment from 'moment';

    @Component({
        components: {CreateMob, MobCard}
    })
    export default class WeekView extends Vue {
        @Prop({required: false, default: null}) user!: IUser;

        socialMobs: IWeekMobs = {};
        moment = moment;

        async created() {
            const response = await axios.get<IWeekMobs>('/social_mob/week');
            this.socialMobs = response.data;
        }

        get days(): string[] {
            return Object.keys(this.socialMobs);
        }
    }
</script>

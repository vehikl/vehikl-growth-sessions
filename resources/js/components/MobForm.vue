<template>
    <form class="create-mob bg-white w-full p-4 text-left" @submit.prevent>
        <div class="mb-4">
            <label for="topic" class="block text-gray-700 text-sm font-bold mb-2">
                Topic
            </label>
            <input id="topic"
                   v-model="topic"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                   type="text" placeholder="What is this mob about?">
        </div>

        <div class="mb-4">
            <label for="time" class="block text-gray-700 text-sm font-bold mb-2">
                Time
            </label>
            <vue-timepicker v-model="time"
                            tabindex="-1"
                            hide-disabled-items
                            :hour-range="[['1p', '5p']]"
                            format="hh:mm a"
                            :minute-interval="15"
                            id="time"/>
        </div>

        <div class="mb-4">
            <label for="location" class="block text-gray-700 text-sm font-bold mb-2">
                Location
            </label>
            <input id="location"
                   v-model="location"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                   type="text"
                   placeholder="Where should people go to participate?">
        </div>

        <button
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
            type="submit"
            @click="createMob">
            Create
        </button>
    </form>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {IUser, ISocialMob} from '../types';
    import VueTimepicker from 'vue2-timepicker'
    import axios from 'axios';

    @Component({components: {VueTimepicker}})
    export default class CreateMob extends Vue {
        @Prop({required: true}) owner!: IUser;
        @Prop({required: false, default: ''}) startDate!: string;

        time: string = '03:30 pm';
        location: string = '';
        topic: string = '';
        date: string = '';

        mounted() {
            this.date = this.startDate;
            let input = document.getElementById("topic");
            if (input) {
                input.focus();
            }
        }

        async createMob() {
            try {
                let response = await axios.post<ISocialMob>('social_mob', {
                    location: this.location,
                    topic: this.topic,
                    start_time: `${this.date} ${this.time}`
                });

                this.$emit('mob-created', response.data);
            } catch (e) {
                alert('Something went wrong :(');
            }
        }
    }
</script>

<style lang="scss" scoped>
</style>

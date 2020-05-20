<template>
    <form class="create-mob bg-white max-w-lg mx-auto py-4">
        <div class="mb-4">
            <label for="topic" class="block text-gray-700 text-sm font-bold mb-2">
                Topic
            </label>
            <input id="topic" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" placeholder="What is this mob about?">
        </div>

        <div class="mb-12">
            <label for="location" class="block text-gray-700 text-sm font-bold mb-2">
                Location
            </label>
            <input id="location" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" placeholder="What is this mob about?">
        </div>

        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
            Create
        </button>
    </form>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {IUser} from '../types';

    @Component
    export default class CreateMob extends Vue {
        @Prop({required: true}) owner!: IUser;
    }
</script>

<style lang="scss" scoped>
</style>

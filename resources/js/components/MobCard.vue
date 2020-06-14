<template>
    <div class="bg-gray-100 border border-blue-300 shadow p-6 rounded-lg hover:bg-blue-100 cursor-pointer"
         @click="goToMob">
        <div class="flex items-center">
            <v-avatar :src="socialMob.owner.avatar" :alt="`${socialMob.owner.name}'s Avatar`" size="12"/>
            <h3 class="ml-6 text-lg" v-text="socialMob.owner.name"/>
        </div>
        <pre class="my-4 inline-block text-left whitespace-pre-wrap max-h-64 overflow-y-auto overflow-x-hidden font-sans"
             v-text="socialMob.topic"/>
        <div class="flex justify-between mb-2 text-blue-700">
            <div class="flex items-center attendees-count">
                <i class="fa fa-user-circle text-lg mr-2" aria-hidden="true"></i>
                <span v-text="socialMob.attendees.length"/>
            </div>
            <div class="flex items-center">
                <i class="fa fa-clock-o text-lg mr-2" aria-hidden="true"></i>
                {{socialMob.startTime}} to {{socialMob.endTime}}
            </div>
        </div>

        <div class="text-blue-700 text-left mb-4 break-all">
            <i class="fa fa-compass text-xl mr-1" aria-hidden="true"></i>
            <a @click.stop v-if="socialMob.isLocationAnUrl" class="location underline" :href="socialMob.location" target="_blank" v-text="socialMob.location"/>
            <span v-else class="location" v-text="socialMob.location"/>
        </div>

        <button class="join-button w-32 bg-blue-500 hover:bg-blue-700 focus:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                @click.stop="joinMob"
                v-show="socialMob.canJoin(user)">
            Join
        </button>
        <button class="leave-button w-32 bg-red-500 hover:bg-red-700 focus:bg-red-700  text-white font-bold py-2 px-4 rounded"
                @click.stop="leaveMob"
                v-show="socialMob.canLeave(user)">
            Leave
        </button>
        <div  v-show="socialMob.canEditOrDelete(user)">
            <button class="update-button w-32 bg-orange-500 hover:bg-orange-700 focus:bg-orange-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="$emit('edit-requested', socialMob)">
                Edit
            </button>
            <button class="delete-button w-16 bg-red-500 hover:bg-red-700 focus:bg-red-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="onDeleteClicked">
                <i class="fa fa-trash" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {SocialMobApi} from '../services/SocialMobApi';
    import {SocialMob} from '../classes/SocialMob';
    import {IUser} from '../types';
    import VAvatar from './VAvatar.vue';
    @Component({
        components: {VAvatar}
    })
    export default class MobCard extends Vue {
        @Prop({required: true}) socialMob!: SocialMob;
        @Prop({required: false, default: null}) user!: IUser;

        goToMob() {
            window.location.assign(SocialMobApi.showUrl(this.socialMob));
        }

        async joinMob() {
            await SocialMobApi.join(this.socialMob);
            this.$emit('mob-updated');
        }

        async leaveMob() {
            await SocialMobApi.leave(this.socialMob);
            this.$emit('mob-updated');
        }

        async onDeleteClicked() {
            if (confirm('Are you sure you want to delete?')) {
                await SocialMobApi.delete(this.socialMob);
                this.$emit('delete-requested', this.socialMob);
            }
        }
    }
</script>

<style lang="scss" scoped>
</style>

<template>
    <a class="bg-gray-100 max-w-sm block p-4 border-gray-300 border rounded-lg">
        <div class="flex items-start">
            <div class="bg-gray-600 text-white rounded h-16 w-16 flex flex-col items-start justify-center px-2 leading-none text-lg">
                11:30
                <div class="text-xs">PM</div>
            </div>
            <div class="ml-2">
                <h2 class="font-semibold text-lg mb-2">Finishing Off our multi-group drop down menu</h2>
                <p class="text-gray-600 mb-2">Darren Galway</p>
                <span class="bg-gray-300 rounded-full px-3 py-1 text-gray-600 text-sm">Private</span>
                <span class="bg-gray-300 rounded-full px-3 py-1 text-gray-600 text-sm">1 hour</span>
                <div class="flex items-center mt-5">
                    <p class="text-gray-700">4 spots remaining</p>
                    <button class="ml-auto text-blue-500 font-bold text">Join</button>
                </div>
            </div>
        </div>
    </a>
</template>

<script lang="ts">
import {Component, Prop, Vue} from 'vue-property-decorator';
import {GrowthSessionApi} from '../services/GrowthSessionApi';
import {GrowthSession} from '../classes/GrowthSession';
import {IUser} from '../types';
import VAvatar from './VAvatar.vue';
import IconDraggable from '../svgs/IconDraggable.vue';
import LocationRenderer from './LocationRenderer.vue';

@Component({
    components: {VAvatar, IconDraggable, LocationRenderer}
})
export default class GrowthSessionCard extends Vue {
    @Prop({required: true}) growthSession!: GrowthSession;
    @Prop({required: false, default: null}) user!: IUser;

    get isDraggable(): boolean {
        return this.growthSession.canEditOrDelete(this.user);
    }

    get growthSessionUrl() {
        return GrowthSessionApi.showUrl(this.growthSession);
    }

    async joinGrowthSession() {
        await this.growthSession.join();
        this.$emit('growth-session-updated');
    }

    async leaveGrowthSession() {
        await this.growthSession.leave();
        this.$emit('growth-session-updated');
    }

    async onDeleteClicked() {
        if (confirm('Are you sure you want to delete?')) {
            await this.growthSession.delete();
            this.$emit('delete-requested', this.growthSession);
        }
    }
}
</script>


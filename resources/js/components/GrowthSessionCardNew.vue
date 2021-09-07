<template>
    <a class="bg-gray-100 max-w-sm block p-4 border-gray-300 border rounded-lg">
        <div class="flex items-start">
            <div class="bg-gray-600 text-white rounded h-16 w-16 flex flex-col items-start justify-center px-2 leading-none text-lg">
                11:30
                <div class="text-xs">PM</div>
            </div>
            <div class="ml-2 text-left">
                <h2 class="font-semibold text-lg mb-2">Finishing Off our multi-group drop down menu</h2>
                <div class="flex">
                    <p class="text-gray-600 mb-2 mr-2">Darren Galway</p>
                    <v-avatar src="https://avatars.githubusercontent.com/u/6425636?s=200&v=4" alt="Someone's Avatar"
                              size="6"/>
                </div>

                <span class="bg-gray-300 rounded-full px-3 py-1 text-gray-600 text-sm"><i :class="[isPublic ? 'fa-unlock' : 'fa-lock' ,'fa', 'mr-1']" aria-hidden="true"></i>{{isPublic? 'Public' : 'Private'}}</span>

                <span class="bg-gray-300 rounded-full px-3 py-1 text-gray-600 text-sm"><i class="fa fa-clock-o mr-1" aria-hidden="true"></i>1 hour</span>
                <div class="flex items-center mt-5">
                    <i class="fa fa-users mr-2 text-gray-600" aria-hidden="true"></i>
                    <p class="text-gray-700">4 spots remaining</p>
                    <button class="hover:bg-indigo-200 p-2 rounded ml-auto text-indigo-500 font-bold text">Join</button>
                </div>
            </div>
        </div>
    </a>
</template>

<script lang="ts">
import {Component, Vue} from 'vue-property-decorator';
import VAvatar from './VAvatar.vue';
import IconDraggable from '../svgs/IconDraggable.vue';
import LocationRenderer from './LocationRenderer.vue';

@Component({
    components: {VAvatar, IconDraggable, LocationRenderer}
})
export default class GrowthSessionCardNew extends Vue {
    isPublic: boolean = false;

}
</script>


<template>
    <button
        :class="`w-32 ${colorClasses} text-white font-bold py-2 px-4 mx-1 rounded`"
        @click.prevent="() => $emit('click')"
    >
        {{ text }}
    </button>
</template>
<script lang="ts">
    import { Component, Prop, Vue } from "vue-property-decorator";

    @Component({})
    export default class GrowthSessionView extends Vue {
        @Prop({required: true}) text!: string;
        @Prop({required: false, default: 'blue'}) color: 'blue' | 'orange' | 'red';

        get colorClasses() {
            const mapping = {
                blue: 'bg-blue-500 hover:bg-blue-700 focus:bg-blue-700',
                orange: 'bg-orange-500 hover:bg-orange-700 focus:bg-orange-700',
                red: 'bg-red-500 hover:bg-red-700 focus:bg-red-700',
            };
            return mapping[this.color];
        }
    }
</script>

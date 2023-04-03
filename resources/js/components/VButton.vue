<template>
    <button
        :class="`join-button w-32 bg-${color}-500 hover:bg-${color}-700 focus:bg-${color}-700 text-white font-bold py-2 px-4 mx-1 rounded`"
        @click="$emit('click')"
    >
        {{ text }}
    </button>
</template>
<script lang="ts">
    import { Component, Prop, Vue } from "vue-property-decorator";

    @Component({})
    export default class GrowthSessionView extends Vue {
        @Prop({required: true}) text!: string;
        // @Prop({required: true}) onClick!: () => void;
        @Prop({required: false, default: 'blue'}) color: 'blue' | 'orange' | 'red';
    }
</script>

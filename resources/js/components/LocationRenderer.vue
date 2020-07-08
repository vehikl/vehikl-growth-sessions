<template>
    <a v-if="this.isLocationAnURL()" class="underline" :href="locationString" target="_blank"
       v-text="locationString"/>
    <span v-else v-text="locationString"></span>
</template>

<script lang='ts'>
    import {Component, Prop, Vue} from "vue-property-decorator";

    @Component
    export default class LocationRenderer extends Vue {
        @Prop({required: true}) locationString: string;

        isLocationAnURL() {
            try {
                new URL(this.locationString);
                return true;
            } catch {
                return false;
            }
        }
    }
</script>

<style scoped>
</style>

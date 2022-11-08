<template>
    <fieldset class="border inline-flex p-4 rounded border-blue-300 gap-5 mt-5">
        <label>
            All
            <input v-model="visibilityFilterInternal" name="filter-sessions" type="radio" value="all">
        </label>
        <label>
            Private Only
            <input id="private" v-model="visibilityFilterInternal" name="filter-sessions" type="radio" value="private">
        </label>
        <label>
            Public Only
            <input id="public" v-model="visibilityFilterInternal" name="filter-sessions" type="radio" value="public">
        </label>
    </fieldset>
</template>

<script lang="ts">

import {Component, Model, Vue, Watch} from "vue-property-decorator";

@Component
export default class VisibilityRadioFieldset extends Vue {
    @Model('visibility-filter-changed') visibilityFilter!: string;
    visibilityFilterInternal: string = 'all';

    @Watch('visibilityFilterInternal')
    onInput(visibilityValue: string) {
        this.$emit('visibility-filter-changed', visibilityValue);
    }
}
</script>

<style scoped>

</style>

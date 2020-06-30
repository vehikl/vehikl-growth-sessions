<template>
    <datepicker :value="sanitizedDate" @input="onInput"/>
</template>

<script lang=ts>
    import {Component, Model, Vue} from 'vue-property-decorator';
    import Datepicker from 'vuejs-datepicker';
    import {DateTime} from '../classes/DateTime';

    // Fixes https://github.com/charliekassel/vuejs-datepicker/issues/23
    @Component({
        components: {Datepicker}
    })
    export default class DatePicker extends Vue {
        @Model('date-changed') date!: string;

        get sanitizedDate(): string {
            return DateTime.parseByDate(this.date).format('YYYY/MM/DD');
        }

        onInput(value: any) {
            this.$emit('date-changed', DateTime.parseByDate(value).toDateString());
        }
    }
</script>

<style lang="scss" scoped>
</style>

<template>
    <input :value="sanitizedTime" type="time" @input="onInput"/>
</template>

<script lang=ts>
import {Component, Model, Vue} from "vue-property-decorator"
import {DateTime} from "../classes/DateTime"

@Component({})
export default class TimePicker extends Vue {
    @Model("time-changed") time!: string

    get sanitizedTime(): string {
        return DateTime.parseByTime(this.time).toTimeString24Hours()
    }

    onInput({target}) {
        this.$emit("time-changed", DateTime.parseByTime(target.value).toTimeString12Hours())
    }
}
</script>

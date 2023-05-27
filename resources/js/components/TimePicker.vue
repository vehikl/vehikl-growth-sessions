<script lang="ts" setup>
import {DateTime} from "../classes/DateTime"
import {computed} from "vue"

const props = defineProps<{ modelValue: string }>()
const emit = defineEmits(["update:modelValue"])
const time = computed({
    get: () => DateTime.parseByTime(props.modelValue).toTimeString24Hours(),
    set: (value) => emit("update:modelValue", DateTime.parseByTime(value).toTimeString12Hours())
})
</script>

<template>
    <input v-model="time" type="time"/>
</template>

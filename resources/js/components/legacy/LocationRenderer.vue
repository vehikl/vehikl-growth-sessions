<script lang='ts' setup>
import {computed} from "vue"

const props = defineProps<{
    locationString: string;
}>()

const parts = computed(() => {
    const partRegex = /\b[^\s]+\b/g
    const parts = props.locationString.match(partRegex) ?? []
    const gaps = props.locationString.split(partRegex)
    const mappedParts = parts.map((part, i) => ({
        content: part,
        isURL: isURL(part),
        gap: gaps[i]
    }))

    if (gaps.length > mappedParts.length) {
        mappedParts.push({
            content: gaps[gaps.length - 1],
            isURL: false,
            gap: ""
        })
    }

    return mappedParts
})

function isURL(candidate: string): boolean {
    try {
        new URL(candidate)
        return true
    } catch {
        return false
    }
}
</script>

<template>
    <span>
        <template v-for="part in parts">
            <template v-if="part.isURL">{{ part.gap }}<a :href="part.content" class="underline"
                                                         target="_blank">{{ part.content }}</a></template>
            <template v-else>{{ part.gap + part.content }}</template>
        </template>
    </span>
</template>

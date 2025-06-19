<script lang='ts' setup>
import {computed} from "vue"

const props = defineProps<{
    locationString: string;
}>()

const parts = computed(() => {
    const partRegex = /\b[^\s]+\b/g
    const parts = props.locationString.match(partRegex) ?? []
    const gaps = props.locationString.split(partRegex)
    const mappedParts = parts.map((part, i) => {
        const isUrl = isURL(part);
        const stuff = hasHttpPrefix(part)

        if (isUrl && !stuff) {
            part = `https://${part}`
        }

        return ({
            content: part,
            isURL: isUrl,
            gap: gaps[i]
        });
    })

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
    return candidate.match(/.+\..+/g)
}

function hasHttpPrefix(candidate: string): boolean {
    return candidate.includes('http://') || candidate.includes('https://')
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

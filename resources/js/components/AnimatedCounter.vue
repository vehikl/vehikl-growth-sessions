<script lang="ts" setup>
import { computed } from 'vue';

const props = defineProps<{
    value: string;
}>();

type Token = { kind: 'digits' | 'literal'; text: string };

const tokens = computed<Token[]>(() =>
    props.value
        .split(/(\d+)/)
        .filter((part) => part !== '')
        .map((part) => ({ kind: /^\d/.test(part) ? 'digits' : 'literal', text: part })),
);

/** A CSS string literal, so a quote in the formatted value cannot break out of the declaration. */
function toCssString(text: string): string {
    return `"${text.replace(/[\\"]/g, '\\$&')}"`;
}
</script>

<template>
    <span class="gs-counter">
        <span class="sr-only">{{ value }}</span>

        <span data-testid="counter-layers" class="gs-counter-layers" aria-hidden="true">
            <span data-testid="counter-ghost" class="gs-counter-ghost" :style="{ '--gs-num-full': toCssString(value) }"></span>

            <span :key="value" data-testid="counter-animation" class="gs-counter-run">
                <template v-for="(token, index) in tokens" :key="index">
                    <span
                        v-if="token.kind === 'digits'"
                        data-testid="counter-digits"
                        class="gs-counter-digits"
                        :style="{ '--gs-num-to': token.text }"
                    >
                    </span>
                    <span
                        v-else
                        data-testid="counter-literal"
                        class="gs-counter-literal"
                        :style="{ '--gs-num-text': toCssString(token.text) }"
                    ></span>
                </template>
            </span>
        </span>
    </span>
</template>

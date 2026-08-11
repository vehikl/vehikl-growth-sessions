<script lang="ts" setup>
import { computed } from 'vue';

const props = defineProps<{
    /** The finished value, already formatted — "11,229", "193h 30m", "42". */
    value: string;
}>();

type Token = { kind: 'digits' | 'literal'; text: string };

/**
 * The climbing digits are drawn as generated content, so every visible character has to
 * live in CSS rather than in the DOM — otherwise the tile would carry its value twice, once
 * for the eye and once for the screen reader beside it. Splitting on runs of digits leaves
 * the numbers to animate, and hands back whatever sits between them ("," in 11,229, "h " in
 * 193h 30m) to be echoed verbatim.
 */
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
            <!-- Holds the tile at the width of the finished number so a count climbing past 99
                 into 100 widens into space already reserved rather than nudging its label. Drawn
                 as generated content like the digits are, so it stays out of the DOM's text. -->
            <span data-testid="counter-ghost" class="gs-counter-ghost" :style="{ '--gs-num-full': toCssString(value) }"></span>

            <!-- Keyed on the value so a new number gets a new element, and therefore a fresh
                 animation — a finished one would otherwise sit on the old count. -->
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

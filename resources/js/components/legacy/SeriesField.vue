<script lang="ts" setup>
import { ChevronDown } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

/**
 * The one control that files a growth session under a series.
 *
 * A text field rather than a picker, because a series is its name: typing one you already run
 * files the session under it, and typing anything else starts a thread by being the first session
 * in it. Your own threads are offered underneath as you type, so joining one is a click rather than
 * an exact spelling - and only your own, since a series is somebody's to run and nobody else may
 * file anything under it.
 *
 * The list is drawn here rather than left to a native `<datalist>`, which browsers render in their
 * own chrome - unthemeable, mispositioned against our own inputs, and unreachable from a spec.
 */
const props = withDefaults(
    defineProps<{
        modelValue: string;
        options: string[];
        id: string;
        placeholder?: string;
        invalid?: boolean;
    }>(),
    { placeholder: 'e.g. Vue Deep Dive', invalid: false },
);

const emit = defineEmits<{ 'update:modelValue': [string] }>();

const isOpen = ref(false);
const highlighted = ref(-1);
const field = ref<HTMLInputElement | null>(null);

/**
 * What is offered as you type. An empty field offers everything, so the control still answers "what
 * am I already running?" before a single key is pressed; anything else narrows by what was typed.
 * A field holding exactly one thread's name offers no suggestion - there is nothing left to pick.
 */
const suggestions = computed<string[]>(() => {
    const typed = props.modelValue.trim().toLocaleLowerCase();

    if (typed === '') return props.options;
    if (props.options.length === 1 && props.options[0].toLocaleLowerCase() === typed) return [];

    return props.options.filter((option) => option.toLocaleLowerCase().includes(typed));
});

const isShowingList = computed(() => isOpen.value && suggestions.value.length > 0);

function open() {
    isOpen.value = true;
    highlighted.value = -1;
}

function close() {
    isOpen.value = false;
    highlighted.value = -1;
}

function onInput(event: Event) {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
    open();
}

function choose(option: string) {
    emit('update:modelValue', option);
    close();
    field.value?.focus();
}

/** The chevron opens the list on a field nobody has typed in yet, and closes it again. */
async function toggle() {
    if (isOpen.value) return close();

    open();
    await nextTick();
    field.value?.focus();
}

/**
 * Walk the suggestions, wrapping through "nothing highlighted" at either end so arrowing past the
 * last one hands the field back rather than jumping to the first. `-1` is that state, which is why
 * the arithmetic is done one place to the right of it.
 */
function move(step: number) {
    if (!isShowingList.value) return open();

    const places = suggestions.value.length + 1;
    highlighted.value = ((highlighted.value + 1 + step + places) % places) - 1;
}

/** Enter takes the highlighted suggestion if there is one, and otherwise leaves what was typed. */
function confirm(event: KeyboardEvent) {
    if (!isShowingList.value || highlighted.value < 0) return close();

    // Only swallow the key when it actually picked something, so Enter still submits the form.
    event.preventDefault();
    choose(suggestions.value[highlighted.value]);
}

/**
 * Closing on blur would fire before the click that caused it, so the list is dismissed only once
 * focus has settled somewhere outside the control entirely.
 */
/** So a caller opening the control for the first time can put the cursor in it. */
function focus() {
    field.value?.focus();
}

defineExpose({ focus });

function onFocusOut(event: FocusEvent) {
    const movedTo = event.relatedTarget as Node | null;

    if (!movedTo || !(event.currentTarget as HTMLElement).contains(movedTo)) close();
}
</script>

<template>
    <div class="series-field relative" @focusout="onFocusOut">
        <input
            :id="id"
            ref="field"
            :value="modelValue"
            :class="{ 'error-outline': invalid }"
            class="gs-input w-full rounded-lg py-2.5 pr-10 pl-3 text-sm"
            :placeholder="placeholder"
            maxlength="45"
            type="text"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="isShowingList"
            :aria-controls="`${id}-suggestions`"
            autocomplete="off"
            @input="onInput"
            @focus="open"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter="confirm"
            @keydown.esc="close"
        />

        <button
            type="button"
            class="gs-text-muted hover:text-gs-accent transition-smooth absolute top-1/2 right-2.5 -translate-y-1/2 cursor-pointer p-0.5"
            :aria-label="isShowingList ? 'Hide your series' : 'Show your series'"
            tabindex="-1"
            @mousedown.prevent
            @click="toggle"
        >
            <ChevronDown aria-hidden="true" :size="16" :stroke-width="2" :class="{ 'rotate-180': isShowingList }" class="transition-smooth" />
        </button>

        <ul
            v-if="isShowingList"
            :id="`${id}-suggestions`"
            role="listbox"
            class="gs-card gs-border absolute z-30 mt-1 max-h-52 w-full overflow-y-auto rounded-lg border py-1 shadow-lg"
        >
            <li v-for="(option, index) in suggestions" :key="option" role="option" :aria-selected="index === highlighted">
                <button
                    type="button"
                    class="transition-smooth block w-full cursor-pointer truncate px-3 py-2 text-left text-sm"
                    :class="index === highlighted ? 'bg-vehikl-orange/10 gs-accent-text font-semibold' : 'gs-text-strong hover:bg-vehikl-orange/10'"
                    @mousedown.prevent
                    @click="choose(option)"
                    @mouseenter="highlighted = index"
                >
                    {{ option }}
                </button>
            </li>
        </ul>
    </div>
</template>

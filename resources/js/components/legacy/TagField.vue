<script lang="ts" setup>
import { IDropdownOption } from '@/types/IDropdownOption';
import { Check, ChevronDown, X } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

/**
 * Multi-select for a growth session's tags. Chosen tags show as removable chips; the rest of the
 * fixed vocabulary stays in the list beneath. Picking leaves the list open, since tagging is
 * usually plural.
 */
const props = withDefaults(
    defineProps<{
        modelValue: string[];
        options: IDropdownOption[];
        id: string;
        placeholder?: string;
        invalid?: boolean;
    }>(),
    { placeholder: 'Search tags', invalid: false },
);

const emit = defineEmits<{ 'update:modelValue': [string[]] }>();

const isOpen = ref(false);
const highlighted = ref(-1);
const search = ref('');
const field = ref<HTMLInputElement | null>(null);
const root = ref<HTMLElement | null>(null);
const opensAbove = ref(false);
const LIST_MAX_HEIGHT = 224;
const LIST_GAP = 4;

/** The chosen tags, in the order the vocabulary lists them rather than the order they were picked. */
const chosen = computed<IDropdownOption[]>(() => props.options.filter((option) => props.modelValue.includes(option.value)));

/** Chosen tags stay in the list, carrying a check, so a tag can be taken back off where it was put on. */
const suggestions = computed<IDropdownOption[]>(() => {
    const typed = search.value.trim().toLocaleLowerCase();

    if (typed === '') return props.options;

    return props.options.filter((option) => option.label.toLocaleLowerCase().includes(typed));
});

const isShowingList = computed(() => isOpen.value && suggestions.value.length > 0);

function isChosen(value: string) {
    return props.modelValue.includes(value);
}

function updateListPlacement() {
    const control = root.value;
    if (!control) return;

    const controlBox = control.getBoundingClientRect();
    const dialogBox = control.closest('dialog')?.getBoundingClientRect();
    const boundaryTop = Math.max(0, dialogBox?.top ?? 0);
    const boundaryBottom = Math.min(window.innerHeight, dialogBox?.bottom ?? window.innerHeight);
    const roomBelow = boundaryBottom - controlBox.bottom;
    const roomAbove = controlBox.top - boundaryTop;

    opensAbove.value = roomBelow < LIST_MAX_HEIGHT + LIST_GAP && roomAbove > roomBelow;
}

function open() {
    isOpen.value = true;
    highlighted.value = -1;
    void nextTick(updateListPlacement);
}

function close() {
    isOpen.value = false;
    highlighted.value = -1;
}

function onInput(event: Event) {
    search.value = (event.target as HTMLInputElement).value;
    open();
}

/** Adds the tag, or removes it if already chosen. Clears the search either way, so the list is not narrowed against the next tag. */
function toggle(value: string) {
    emit('update:modelValue', isChosen(value) ? props.modelValue.filter((chosenValue) => chosenValue !== value) : [...props.modelValue, value]);

    search.value = '';
    highlighted.value = -1;
    field.value?.focus();
}

function remove(value: string) {
    emit(
        'update:modelValue',
        props.modelValue.filter((chosenValue) => chosenValue !== value),
    );
    field.value?.focus();
}

async function toggleList() {
    if (isOpen.value) return close();

    open();
    await nextTick();
    field.value?.focus();
}

/**
 * Walk the suggestions, wrapping through "nothing highlighted" (`-1`) at either end so arrowing
 * past the last one hands the field back. The arithmetic is offset by one to make room for `-1`.
 */
function move(step: number) {
    if (!isShowingList.value) return open();

    const places = suggestions.value.length + 1;
    highlighted.value = ((highlighted.value + 1 + step + places) % places) - 1;
}

/** Enter takes the highlighted tag if there is one, and otherwise leaves the form to submit. */
function confirm(event: KeyboardEvent) {
    if (!isShowingList.value || highlighted.value < 0) return close();

    // Only swallow the key when it picked something, so Enter still submits the form.
    event.preventDefault();
    toggle(suggestions.value[highlighted.value].value);
}

/** Backspace on an empty search removes the last chip. */
function onBackspace() {
    if (search.value !== '' || chosen.value.length === 0) return;

    remove(chosen.value[chosen.value.length - 1].value);
}

/** The list scrolls, so keyboard navigation has to keep the highlighted option in view. */
watch(highlighted, async (index) => {
    if (index < 0) return;

    await nextTick();
    document.getElementById(`${props.id}-option-${suggestions.value[index].value}`)?.scrollIntoView({ block: 'nearest' });
});

/** Blur fires before the click that caused it, so only close once focus has left the control. */
function onFocusOut(event: FocusEvent) {
    const movedTo = event.relatedTarget as Node | null;

    if (!movedTo || !(event.currentTarget as HTMLElement).contains(movedTo)) close();
}

function focus() {
    field.value?.focus();
}

defineExpose({ focus });
</script>

<template>
    <div ref="root" class="tag-field relative" @focusout="onFocusOut">
        <div
            :class="{ 'error-outline': invalid }"
            class="gs-input focus-within:border-vehikl-orange flex w-full flex-wrap items-center gap-1.5 rounded-lg py-2 pr-10 pl-2.5"
        >
            <span
                v-for="tag in chosen"
                :key="tag.value"
                :data-testid="`tag-chip-${tag.value}`"
                class="gs-header-bg inline-flex items-center gap-1 rounded-full py-1 pr-1 pl-2.5 text-xs font-semibold tracking-[0.05em] text-white uppercase"
            >
                {{ tag.label }}
                <button
                    type="button"
                    class="transition-smooth cursor-pointer rounded-full p-0.5 hover:bg-white/25"
                    :aria-label="`Remove ${tag.label}`"
                    @click="remove(tag.value)"
                >
                    <X aria-hidden="true" :size="12" :stroke-width="2.5" />
                </button>
            </span>

            <input
                :id="id"
                ref="field"
                :value="search"
                class="min-w-24 flex-1 bg-transparent py-0.5 text-sm outline-none"
                :placeholder="chosen.length ? '' : placeholder"
                type="text"
                role="combobox"
                aria-autocomplete="list"
                :aria-expanded="isShowingList"
                :aria-controls="`${id}-suggestions`"
                :aria-activedescendant="highlighted >= 0 ? `${id}-option-${suggestions[highlighted].value}` : undefined"
                autocomplete="off"
                @input="onInput"
                @focus="open"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter="confirm"
                @keydown.esc="close"
                @keydown.backspace="onBackspace"
            />
        </div>

        <button
            type="button"
            class="gs-text-muted hover:text-gs-accent transition-smooth absolute top-2.5 right-2.5 cursor-pointer p-0.5"
            :aria-label="isShowingList ? 'Hide tags' : 'Show tags'"
            tabindex="-1"
            @mousedown.prevent
            @click="toggleList"
        >
            <ChevronDown aria-hidden="true" :size="16" :stroke-width="2" :class="{ 'rotate-180': isShowingList }" class="transition-smooth" />
        </button>

        <!--
            The list is positioned against this control rather than the form, then flipped upward
            when the field is near the bottom of a dialog.
            `max-h-56` is six rows exactly - six 36px rows plus this list's own 4px of padding top
            and bottom - so the cap lands between rows instead of slicing one in half.
        -->
        <ul
            v-if="isShowingList"
            :id="`${id}-suggestions`"
            role="listbox"
            aria-multiselectable="true"
            class="gs-card gs-border absolute z-30 max-h-56 w-full overflow-y-auto rounded-lg border py-1 shadow-lg"
            :class="opensAbove ? 'bottom-full mb-1' : 'top-full mt-1'"
        >
            <li
                v-for="(option, index) in suggestions"
                :id="`${id}-option-${option.value}`"
                :key="option.value"
                role="option"
                :aria-selected="isChosen(option.value)"
            >
                <button
                    type="button"
                    :data-testid="`tag-option-${option.value}`"
                    class="transition-smooth flex w-full cursor-pointer items-center gap-2 px-3 py-2 text-left text-sm"
                    :class="index === highlighted ? 'bg-vehikl-orange/10 gs-accent-text font-semibold' : 'gs-text-strong hover:bg-vehikl-orange/10'"
                    @mousedown.prevent
                    @click="toggle(option.value)"
                    @mouseenter="highlighted = index"
                >
                    <Check
                        aria-hidden="true"
                        :size="14"
                        :stroke-width="3"
                        class="shrink-0"
                        :class="isChosen(option.value) ? 'text-vehikl-orange' : 'invisible'"
                    />
                    <span class="truncate">{{ option.label }}</span>
                </button>
            </li>
        </ul>
    </div>
</template>

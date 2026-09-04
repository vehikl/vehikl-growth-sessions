<script lang="ts" setup>
import { IDropdownOption } from '@/types/IDropdownOption';
import { Check, ChevronDown, X } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

/**
 * The control that tags a growth session.
 *
 * A picker rather than a text field, because a tag is one of a fixed set somebody already decided
 * on - typing a name that isn't in it tags nothing, so the field only ever offers what exists. What
 * has been chosen sits in the field as removable chips, and the rest of the vocabulary stays a
 * keystroke away in the list beneath, so the set is still browsable by someone who doesn't know it.
 *
 * Picking does not close the list: tagging is usually plural, and reopening between every tag costs
 * more than the list costs while it is open.
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

/**
 * What the list offers. Chosen tags stay in it, carrying a check, so the list always answers "what
 * can this be tagged?" in full and a tag can be taken back off where it was put on.
 */
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

/**
 * Adds the tag, or takes it back off if it was already on. The search is spent either way: it found
 * what it was typed for, and leaving it would narrow the list against the next tag being looked for.
 */
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

/** The chevron opens the list on a field nobody has typed in yet, and closes it again. */
async function toggleList() {
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

/** Enter takes the highlighted tag if there is one, and otherwise leaves the form to submit. */
function confirm(event: KeyboardEvent) {
    if (!isShowingList.value || highlighted.value < 0) return close();

    // Only swallow the key when it actually picked something, so Enter still submits the form.
    event.preventDefault();
    toggle(suggestions.value[highlighted.value].value);
}

/** Backspace on an empty search takes the last tag back off, the way it does in any chip field. */
function onBackspace() {
    if (search.value !== '' || chosen.value.length === 0) return;

    remove(chosen.value[chosen.value.length - 1].value);
}

/**
 * The list is taller than it shows, so walking it from the keyboard has to bring the highlighted tag
 * with it - otherwise arrowing past the sixth row highlights something nobody can see.
 */
watch(highlighted, async (index) => {
    if (index < 0) return;

    await nextTick();
    document.getElementById(`${props.id}-option-${suggestions.value[index].value}`)?.scrollIntoView({ block: 'nearest' });
});

/**
 * Closing on blur would fire before the click that caused it, so the list is dismissed only once
 * focus has settled somewhere outside the control entirely.
 */
function onFocusOut(event: FocusEvent) {
    const movedTo = event.relatedTarget as Node | null;

    if (!movedTo || !(event.currentTarget as HTMLElement).contains(movedTo)) close();
}

/** So a caller opening the control for the first time can put the cursor in it. */
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

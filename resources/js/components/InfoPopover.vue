<script lang="ts" setup>
import { onClickOutside, useEventListener } from '@vueuse/core';
import { ref, useId } from 'vue';

defineProps<{ label: string }>();

const panelId = useId();
const open = ref(false);
const root = ref<HTMLElement | null>(null);

onClickOutside(root, () => (open.value = false));
useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    if (event.key === 'Escape') open.value = false;
});
</script>

<template>
    <span ref="root" class="relative inline-flex">
        <button
            type="button"
            data-testid="info-popover-trigger"
            class="gs-text-muted transition-smooth flex h-5 w-5 cursor-pointer items-center justify-center rounded-full text-sm hover:bg-black/5 hover:text-current dark:hover:bg-white/10"
            :aria-label="label"
            :aria-expanded="open"
            :aria-controls="panelId"
            @click="open = !open"
        >
            <i class="fa fa-info-circle" aria-hidden="true"></i>
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="scale-95 opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="scale-95 opacity-0"
        >
            <span
                v-if="open"
                :id="panelId"
                data-testid="info-popover-panel"
                role="note"
                class="gs-card gs-border gs-text-body absolute top-full left-0 z-20 mt-2 block w-64 origin-top-left rounded-xl border p-3 text-sm font-normal shadow-lg"
            >
                <slot />
            </span>
        </Transition>
    </span>
</template>

<script setup lang="ts">
import ConfirmationModal from '@/components/legacy/ConfirmationModal.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const calendarUrl = computed(() => (page.props.auth as any).calendar_url as string | null);
const showPopover = ref(false);
const copied = ref(false);
const popoverRef = ref<HTMLElement | null>(null);
const confirmState = ref<'open' | 'closed'>('closed');
const regenerated = ref(false);

function onClickOutside(event: MouseEvent) {
    if (popoverRef.value && !popoverRef.value.contains(event.target as Node)) {
        showPopover.value = false;
    }
}

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));

function generateToken() {
    confirmState.value = 'closed';
    router.post(route('calendar.generate-token'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            regenerated.value = true;
            setTimeout(() => (regenerated.value = false), 2000);
        },
    });
}

function copyUrl() {
    if (calendarUrl.value) {
        navigator.clipboard.writeText(calendarUrl.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    }
}
</script>

<template>
    <div ref="popoverRef" class="relative">
        <button
            class="mt-4 flex items-center uppercase tracking-wider hover:text-vehikl-orange transition-smooth lg:mt-0"
            @click="showPopover = !showPopover"
        >
            <i class="fa fa-calendar mr-2" aria-hidden="true"></i>
            Calendar
        </button>

        <div
            v-if="showPopover"
            class="absolute right-0 top-full z-50 mt-2 w-80 rounded-lg bg-white p-4 shadow-lg text-gray-800 normal-case tracking-normal"
        >
            <div class="mb-2 text-sm font-semibold uppercase tracking-wider text-gray-600">Calendar Feed</div>

            <template v-if="calendarUrl">
                <p class="mb-2 text-xs text-gray-500">
                    Subscribe to this URL in your calendar to stay in sync with your growth sessions.
                </p>
                <div class="mb-3 flex items-center gap-2">
                    <input
                        :value="calendarUrl"
                        readonly
                        class="flex-1 rounded border border-gray-300 px-2 py-1 text-xs font-mono bg-gray-50 truncate"
                        @focus="($event.target as HTMLInputElement).select()"
                    />
                    <button
                        class="shrink-0 rounded bg-vehikl-orange px-3 py-1 text-xs font-semibold text-white hover:bg-orange-600 transition-smooth"
                        @click.stop="copyUrl"
                    >
                        {{ copied ? 'Copied!' : 'Copy' }}
                    </button>
                </div>
                <button
                    :class="regenerated ? 'text-xs text-green-500' : 'text-xs text-gray-400 hover:text-red-500 transition-smooth'"
                    :disabled="regenerated"
                    @click.stop="confirmState = 'open'"
                >
                    {{ regenerated ? 'New link ready' : 'Regenerate link' }}
                </button>
            </template>

            <template v-else>
                <p class="mb-3 text-xs text-gray-500">
                    Generate a personal calendar link to subscribe to your growth sessions in any calendar app.
                </p>
                <button
                    class="w-full rounded bg-vehikl-orange px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600 transition-smooth"
                    @click.stop="generateToken"
                >
                    Generate Calendar Link
                </button>
            </template>
        </div>
    </div>

    <ConfirmationModal
        :state="confirmState"
        title="Regenerate link?"
        message="Your old link will stop working. You'll need to re-subscribe with the new link in your calendar app."
        confirm-label="Regenerate"
        @confirmed="generateToken"
        @dismissed="confirmState = 'closed'"
    />
</template>

<script setup lang="ts">
import { notificationSentence } from '@/lib/notificationSentence';
import { NotificationApi } from '@/services/NotificationApi';
import type { INotification } from '@/types';
import { onClickOutside } from '@vueuse/core';
import moment from 'moment-timezone';
import { computed, onMounted, ref } from 'vue';

/** What a row actually renders. Derived once per payload, not once per render. */
interface NotificationRow {
    id: number;
    sentence: string;
    createdAt: string;
}

const notifications = ref<INotification[]>([]);
const loaded = ref(false);
const open = ref(false);
const root = ref<HTMLElement | null>(null);

onClickOutside(root, () => (open.value = false));

const rows = computed<NotificationRow[]>(() =>
    notifications.value.map((notification) => ({
        id: notification.id,
        sentence: notificationSentence(notification),
        createdAt: moment(notification.created_at).format('MMM D, h:mm a'),
    })),
);

onMounted(async () => {
    try {
        notifications.value = await NotificationApi.index();
    } catch {
        // An unreachable endpoint leaves the menu empty rather than taking the header down with it.
        notifications.value = [];
    } finally {
        loaded.value = true;
    }
});
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="transition-smooth flex cursor-pointer items-center rounded-full p-1 text-white/55 ring-white/60 hover:text-white focus:outline-none focus-visible:ring-2"
            data-testid="notifications-trigger"
            :aria-expanded="open"
            aria-haspopup="menu"
            aria-label="Notifications"
            @click="open = !open"
        >
            <i class="fa fa-bell" aria-hidden="true"></i>
            <span
                v-if="rows.length"
                data-testid="notifications-count"
                class="gs-accent-bg ml-1 rounded-full px-1.5 text-[0.65rem] font-bold text-white"
                >{{ rows.length }}</span
            >
        </button>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="gs-card gs-border absolute right-0 z-20 mt-2 w-72 origin-top-right rounded-xl border py-1 shadow-lg"
                role="menu"
                data-testid="notifications-panel"
            >
                <div class="gs-border border-b px-4 py-3">
                    <p class="gs-text-muted text-xs font-bold tracking-[0.06em] uppercase">Notifications</p>
                </div>

                <p v-if="loaded && !rows.length" class="gs-text-muted px-4 py-3 text-sm" data-testid="notifications-empty">Nothing yet.</p>

                <ul v-else class="max-h-80 overflow-y-auto">
                    <!-- The deps are every value the row renders, so an unchanged row is skipped entirely when the list grows. -->
                    <li
                        v-for="row in rows"
                        :key="row.id"
                        v-memo="[row.sentence, row.createdAt]"
                        class="gs-border border-b px-4 py-2.5 last:border-b-0"
                        data-testid="notification"
                    >
                        <p class="gs-text-strong text-sm" data-testid="notification-sentence">{{ row.sentence }}</p>
                        <p class="gs-text-muted mt-0.5 text-xs" data-testid="notification-created-at">{{ row.createdAt }}</p>
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>

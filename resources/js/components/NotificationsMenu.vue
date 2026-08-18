<script setup lang="ts">
import MemberAvatar from '@/components/MemberAvatar.vue';
import { initiatorName, notificationSentence } from '@/lib/notificationSentence';
import { NotificationApi } from '@/services/NotificationApi';
import type { INotification } from '@/types';
import { useEcho } from '@laravel/echo-vue';
import { onClickOutside } from '@vueuse/core';
import moment from 'moment-timezone';
import { computed, onMounted, ref } from 'vue';

// Whose notifications these are. A prop rather than the page props because the menu only ever
// renders for a signed-in user, and taking it as input keeps the subscription drivable in a test.
const props = defineProps<{ userId: number }>();

/** How many the panel holds: the same window the endpoint serves, so a reload agrees with it. */
const MOST_RECENT = 10;

/** What a row actually renders. Derived once per payload, not once per render. */
interface NotificationRow {
    id: number;
    sentence: string;
    createdAt: string;
    initiatorName: string;
    initiatorAvatar: string | null;
}

const notifications = ref<INotification[]>([]);
const loaded = ref(false);
const open = ref(false);
const root = ref<HTMLElement | null>(null);

onClickOutside(root, () => (open.value = false));

/**
 * Every value a row shows is a pure function of a notification, and a notification never changes
 * once fetched. Rendering them straight from the template would re-derive every row on every
 * render — including each open and close of the panel, and every future push that appends a single
 * item. This memoizes them against the list itself, so the work happens once per payload.
 *
 * The timestamp is absolute rather than "2 minutes ago" for the same reason it is safe to cache: a
 * relative stamp is wrong the moment it is rendered and nothing here re-renders on a timer.
 *
 * The initiator name comes from the sentence builder so the avatar's initials and the subject of
 * the sentence beside it are always the same person, fallback included.
 */
const rows = computed<NotificationRow[]>(() =>
    notifications.value.map((notification) => ({
        id: notification.id,
        sentence: notificationSentence(notification),
        createdAt: moment(notification.created_at).format('MMM D, h:mm a'),
        initiatorName: initiatorName(notification),
        initiatorAvatar: notification.initiator?.avatar ?? null,
    })),
);

onMounted(async () => {
    try {
        // Merged behind whatever has already arrived, not assigned over it: a broadcast can land
        // while this request is in flight, and assigning would drop it.
        const fetched = await NotificationApi.index(MOST_RECENT);

        keep([...notifications.value, ...fetched]);
    } catch {
        // An unreachable endpoint leaves the menu empty rather than taking the header down with it.
    } finally {
        loaded.value = true;
    }
});

/**
 * A pushed notification is the same payload the endpoint returns - the two are pinned to each
 * other by a test - so it needs no translation on the way in. It goes on the front because it was
 * raised just now, which makes it newer than anything already listed.
 */
useEcho(
    `notifications.${props.userId}`,
    '.notification.created',
    (incoming: INotification) => keep([incoming, ...notifications.value]),
    [],
    'private',
);

/**
 * Newest first, no repeats, and never more than the endpoint itself would have served.
 *
 * Repeats are not hypothetical: Echo replays on reconnect, and a notification that arrives while
 * the initial request is in flight comes back in that response too. Two rows sharing an id would
 * collide on the list key.
 */
function keep(inOrder: INotification[]): void {
    const byId = new Map<number, INotification>();

    for (const notification of inOrder) {
        if (notification?.id !== undefined && !byId.has(notification.id)) {
            byId.set(notification.id, notification);
        }
    }

    notifications.value = [...byId.values()].slice(0, MOST_RECENT);
}
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
                    <!-- v-memo must list every value the row renders, or a row goes stale when the list changes around it. -->
                    <li
                        v-for="row in rows"
                        :key="row.id"
                        v-memo="[row.sentence, row.createdAt, row.initiatorName, row.initiatorAvatar]"
                        class="gs-border flex items-start gap-3 border-b px-4 py-2.5 last:border-b-0"
                        data-testid="notification"
                    >
                        <MemberAvatar :name="row.initiatorName" :avatar="row.initiatorAvatar" data-testid="notification-avatar" />

                        <div class="min-w-0">
                            <p class="gs-text-strong text-sm" data-testid="notification-sentence">{{ row.sentence }}</p>
                            <p class="gs-text-muted mt-0.5 text-xs" data-testid="notification-created-at">{{ row.createdAt }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>

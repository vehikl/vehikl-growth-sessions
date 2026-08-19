<script setup lang="ts">
import UserAvatar from '@/components/UserAvatar.vue';
import { useNotifications } from '@/composables/useNotifications';
import type { INotification, INotificationData, INotificationType, IUser } from '@/types';
import { Link } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import { Bell, CalendarDays, CalendarX2, Clock, MapPin, MessageSquare } from 'lucide-vue-next';
import moment from 'moment-timezone';
import type { Component } from 'vue';
import { ref } from 'vue';

const props = defineProps<{ user: IUser }>();

const { notifications, unreadCount, markAllRead } = useNotifications(props.user.id);

const isOpen = ref(false);
const dropdown = ref<HTMLElement | null>(null);
onClickOutside(dropdown, () => (isOpen.value = false));

function toggle() {
    isOpen.value = !isOpen.value;
    if (isOpen.value) markAllRead();
}

/** A short, human "3h ago" style label — the feed favours it over a raw timestamp. */
function timeAgo(isoDate: string): string {
    const seconds = Math.max(0, (Date.now() - new Date(isoDate).getTime()) / 1000);

    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    if (seconds < 2592000) return `${Math.floor(seconds / 86400)}d ago`;

    return new Date(isoDate).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function isLast(notification: INotification): boolean {
    return notifications.value[notifications.value.length - 1]?.id === notification.id;
}

/** One icon per notification type, so a date change reads differently from a location or time change at a glance. */
const TYPE_ICONS: Record<INotificationType, Component> = {
    growth_session_date_changed: CalendarDays,
    growth_session_time_changed: Clock,
    growth_session_location_changed: MapPin,
    growth_session_deleted: CalendarX2,
    growth_session_comment_added: MessageSquare,
};

function notificationIcon(notification: INotification): Component {
    return TYPE_ICONS[notification.data.type] ?? Bell;
}

/** Comments get the commenter's face instead of a generic icon, so they stand out in the feed at a glance. */
function isComment(notification: INotification): boolean {
    return notification.data.type === 'growth_session_comment_added';
}

/** `prefix` and `suffix` sandwich `bold`, split out so markup can style just the name/title. */
type Headline = { prefix?: string; bold: string; suffix: string };

const genericUpdate = ({ title }: INotificationData): Headline => ({ bold: title, suffix: ' was updated.' });

const fieldsUpdated = ({ title, changes }: INotificationData): Headline => ({
    prefix: `${(changes ?? []).map((change) => change.label).join(' & ')} updated for `,
    bold: title,
    suffix: '.',
});

/**
 * The bold lead-in sits between `prefix` and `suffix`, so markup can style just the name/title.
 * One entry per notification type — same shape as `TYPE_ICONS` above — so a new type is a compile
 * error here instead of silently falling through to generic copy.
 */
const HEADLINES: Record<INotificationType, (data: INotificationData) => Headline> = {
    growth_session_date_changed: fieldsUpdated,
    growth_session_time_changed: fieldsUpdated,
    growth_session_location_changed: fieldsUpdated,
    growth_session_comment_added: ({ title, commenter }) => ({ bold: commenter ?? 'Someone', suffix: ` commented on ${title}.` }),
    growth_session_deleted: ({ title, date }) => ({
        bold: title,
        suffix: date ? ` has been cancelled for ${moment(date, 'YYYY-MM-DD').locale('en').format('MMM D')}.` : ' has been cancelled.',
    }),
};

/**
 * Falls back to generic "was updated" copy for a `type` this build doesn't recognize, rather than
 * throwing — old notification rows keep whatever shape they were created with, so a schema change
 * (like the one that split `growth_session_updated` into per-field types) leaves stale rows behind
 * that would otherwise crash the whole bell until they age out of the feed.
 */
function headline(notification: INotification): Headline {
    return (HEADLINES[notification.data.type] ?? genericUpdate)(notification.data);
}
</script>

<template>
    <div ref="dropdown" class="relative">
        <button
            type="button"
            class="transition-smooth relative flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-white/75 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
            :aria-expanded="isOpen"
            aria-haspopup="menu"
            aria-label="Notifications"
            @click="toggle"
        >
            <Bell class="h-[18px] w-[18px]" aria-hidden="true" />
            <span
                v-if="unreadCount > 0"
                class="bg-vehikl-orange absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-bold text-white"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
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
                v-if="isOpen"
                class="gs-card gs-border absolute right-0 z-20 mt-2 max-h-96 w-96 origin-top-right overflow-y-auto rounded-xl border p-4 shadow-lg"
                role="menu"
            >
                <p class="gs-text-strong mb-4 text-xs font-bold tracking-[0.08em] uppercase">Notifications</p>

                <p v-if="notifications.length === 0" class="gs-text-muted py-6 text-center text-sm">You're all caught up.</p>

                <ul v-else role="list" class="-mb-6">
                    <li v-for="notification in notifications" :key="notification.id">
                        <div class="relative pb-6">
                            <span
                                v-if="!isLast(notification)"
                                aria-hidden="true"
                                class="gs-border absolute top-8 left-4 -ml-px h-[calc(100%-2rem)] w-0.5 border-l"
                            />

                            <component
                                :is="notification.data.url ? Link : 'div'"
                                :href="notification.data.url ?? undefined"
                                role="menuitem"
                                class="transition-smooth relative -m-2 flex items-start gap-3 rounded-lg p-2"
                                :class="notification.data.url ? 'hover:bg-black/5 dark:hover:bg-white/5' : ''"
                                @click="notification.data.url && (isOpen = false)"
                            >
                                <span v-if="isComment(notification)" class="relative z-10 flex-none">
                                    <UserAvatar :name="notification.data.commenter ?? 'Someone'" :avatar="notification.data.commenter_avatar" size="h-8 w-8" />
                                    <span class="gs-card gs-border absolute -right-1 -bottom-1 flex h-4 w-4 items-center justify-center rounded-full border">
                                        <MessageSquare class="gs-text-sub h-3 w-3" aria-hidden="true" />
                                    </span>
                                    <span
                                        v-if="!notification.read_at"
                                        class="bg-vehikl-orange gs-card absolute -top-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2"
                                        aria-hidden="true"
                                    />
                                </span>
                                <span v-else class="relative z-10 flex h-8 w-8 flex-none items-center justify-center">
                                    <component :is="notificationIcon(notification)" class="gs-text-sub h-4 w-4" aria-hidden="true" />
                                    <span
                                        v-if="!notification.read_at"
                                        class="bg-vehikl-orange gs-card absolute -top-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2"
                                        aria-hidden="true"
                                    />
                                </span>
                                <span class="min-w-0 flex-1 pt-1">
                                    <span class="gs-text-strong block text-sm leading-snug">
                                        <template v-if="headline(notification).prefix">{{ headline(notification).prefix }}</template
                                        ><span class="font-bold">{{ headline(notification).bold }}</span
                                        >{{ headline(notification).suffix }}
                                    </span>

                                    <span class="gs-text-muted mt-1 block text-xs">{{ timeAgo(notification.created_at) }}</span>
                                </span>
                            </component>
                        </div>
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>

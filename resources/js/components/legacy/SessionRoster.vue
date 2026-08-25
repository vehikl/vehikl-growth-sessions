<script lang="ts" setup>
import { User } from '@/classes/User';
import UserAvatar from '@/components/UserAvatar.vue';
import { ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

/**
 * One roster of people in the session detail drawer.
 *
 * Attendees, the waitlist and the watchers are the same list of members standing in three different
 * roles, so they render through here rather than as three near-copies that drift apart. Everyone
 * listed links to their GitHub profile, whichever roster they are in: an attendee reading as
 * followable while the person queued right behind them reads as inert was never a distinction
 * anybody meant to draw.
 */
const props = withDefaults(
    defineProps<{
        /** Rendered as given, so the caller can carry a count or a capacity, e.g. `ATTENDEES (1/4)`. */
        heading: string;
        members: User[];
        /**
         * For a roster whose order is the point - the queue reads front to back, so it is marked up
         * as an ordered list and announced as one. The order is carried by the sequence itself; no
         * ordinals are drawn, because a member's place is not something the roster sets out to tell
         * them.
         */
        ordered?: boolean;
    }>(),
    { ordered: false },
);

/**
 * Each row paired with what it should be rendered as, decided once per member rather than re-asked
 * by every attribute on the element. A guest whose identity is withheld from this viewer arrives
 * with no nickname (see GuestSafeUser) and has no profile to send anyone to, so that row is a plain
 * span - the alternative being a link to github.com/ that goes nowhere.
 */
const rows = computed(() =>
    props.members.map((member) => ({
        member,
        isLinked: !!member.github_nickname,
        element: member.github_nickname
            ? { is: 'a', href: member.githubURL, target: '_blank', rel: 'noopener noreferrer', class: 'hover:bg-black/5 dark:hover:bg-white/8' }
            : { is: 'span' },
    })),
);
</script>

<template>
    <div>
        <div class="gs-text-muted mb-2.5 text-xs font-bold tracking-[0.06em]">{{ heading }}</div>
        <component :is="ordered ? 'ol' : 'ul'" class="flex flex-col gap-1">
            <li v-for="row in rows" :key="row.member.id" class="flex items-center gap-2">
                <component
                    :is="row.element.is"
                    v-bind="row.element"
                    class="group focus-visible:ring-gs-accent flex min-w-0 flex-1 items-center gap-2.5 rounded-md px-2 py-1.5 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                >
                    <UserAvatar :name="row.member.name" :avatar="row.member.avatar" />
                    <span
                        class="gs-text-strong group-hover:text-gs-accent min-w-0 flex-1 text-sm font-semibold tracking-[0.02em] transition-colors"
                        >{{ row.member.name }}</span
                    >
                    <ChevronRight
                        v-if="row.isLinked"
                        aria-hidden="true"
                        :size="17"
                        :stroke-width="2"
                        class="gs-text-muted group-hover:text-gs-accent flex-none transition-transform group-hover:translate-x-0.5"
                    />
                </component>
            </li>
        </component>
    </div>
</template>

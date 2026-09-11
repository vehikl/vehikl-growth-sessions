<script lang="ts" setup>
import UserAvatar from '@/components/UserAvatar.vue';
import { IMemberSummary } from '@/types';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ members: IMemberSummary[]; max?: number }>(), { max: 5 });

const shown = computed(() => props.members.slice(0, props.max));
const hiddenCount = computed(() => props.members.length - shown.value.length);
const names = computed(() => props.members.map((member) => member.name).join(', '));
</script>

<template>
    <span v-if="members.length" class="attendee-avatars flex items-center" :title="names" :aria-label="`Attending: ${names}`" role="img">
        <UserAvatar
            v-for="member in shown"
            :key="member.id"
            data-testid="attendee-avatar"
            :name="member.name"
            :avatar="member.avatar"
            size="h-6 w-6"
            class="-ml-2 ring-2 ring-(--gs-card) first:ml-0"
        />
        <span
            v-if="hiddenCount > 0"
            data-testid="attendee-overflow"
            class="gs-text-sub -ml-2 flex h-6 w-6 flex-none items-center justify-center rounded-full bg-black/10 text-[10px] font-bold ring-2 ring-(--gs-card) dark:bg-white/15"
            >+{{ hiddenCount }}</span
        >
    </span>
</template>

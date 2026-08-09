<script lang="ts" setup>
import { useInitials } from '@/composables/useInitials';
import { avatarColor } from '@/lib/sessionDisplay';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        name: string;
        avatar?: string | null;
        /** Tailwind sizing utilities, so callers keep control of how big the disc is. */
        size?: string;
        /** Empty by default: the name is usually rendered right beside the avatar. */
        alt?: string;
    }>(),
    { avatar: null, size: 'h-8 w-8', alt: '' },
);

const { getInitials } = useInitials();

/**
 * An avatar URL that no longer resolves would otherwise leave an empty coloured disc, so a
 * failed load falls back to the member's initials.
 */
const hasFailed = ref(false);
watch(
    () => props.avatar,
    () => (hasFailed.value = false),
);

const source = computed(() => (props.avatar && !hasFailed.value ? props.avatar : null));
</script>

<template>
    <span
        :style="{ backgroundColor: avatarColor(name) }"
        :class="['flex flex-none items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white', size]"
    >
        <img v-if="source" :src="source" :alt="alt" class="h-full w-full object-cover" @error="hasFailed = true" />
        <template v-else>{{ getInitials(name) }}</template>
    </span>
</template>

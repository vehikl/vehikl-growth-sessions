<script lang="ts" setup>
import { useInitials } from '@/composables/useInitials';
import { avatarColor } from '@/lib/sessionDisplay';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ name: string; avatar?: string | null; size?: 'sm' | 'md' }>(), { avatar: null, size: 'sm' });

const { getInitials } = useInitials();

const sizeClass = computed(() => (props.size === 'md' ? 'h-10 w-10 text-xs' : 'h-8 w-8 text-[11px]'));
</script>

<template>
    <span
        :style="{ backgroundColor: avatarColor(name) }"
        :class="sizeClass"
        class="flex flex-none items-center justify-center overflow-hidden rounded-full font-bold text-white"
    >
        <img v-if="avatar" :src="avatar" :alt="name" class="h-full w-full object-cover" />
        <template v-else>{{ getInitials(name) }}</template>
    </span>
</template>

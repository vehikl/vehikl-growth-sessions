<script lang="ts" setup>
import LinkifiedText from '@/components/legacy/LinkifiedText.vue';

defineProps<{
    locationString: string;
}>();
</script>

<template>
    <LinkifiedText :text="locationString" />
</template>

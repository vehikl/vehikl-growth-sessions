<script lang="ts" setup>
import VButton from './VButton.vue';
import VModal from './VModal.vue';

interface IProps {
    state: 'open' | 'closed';
    title: string;
    message: string;
    confirmLabel?: string;
}

const props = withDefaults(defineProps<IProps>(), {
    state: 'closed',
    confirmLabel: 'Confirm',
});

const emit = defineEmits(['confirmed', 'dismissed']);

function onModalClosed() {
    if (props.state === 'open') {
        emit('dismissed');
    }
}
</script>

<template>
    <VModal :state="state" @modal-closed="onModalClosed">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="bg-vehikl-orange/10 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full">
                    <i class="fa fa-exclamation-triangle text-vehikl-orange" aria-hidden="true"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-vehikl-dark mb-2 text-lg font-bold">{{ title }}</h3>
                    <p class="text-sm leading-relaxed text-neutral-600">{{ message }}</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-neutral-100 pt-4">
                <VButton text="Cancel" color="blue" variant="outlined" class="!w-auto" @click="emit('dismissed')" />
                <VButton :text="confirmLabel" color="orange" variant="filled" class="confirm-button !w-auto" @click="emit('confirmed')" />
            </div>
        </div>
    </VModal>
</template>

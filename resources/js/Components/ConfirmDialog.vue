<script setup>
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    show: Boolean,
    title: {
        type: String,
        default: 'Confirm delete',
    },
    message: {
        type: String,
        default: 'This action cannot be undone.',
    },
    confirmText: {
        type: String,
        default: 'Delete',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
});

defineEmits(['confirm', 'close']);
</script>

<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ title }}
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                {{ message }}
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton @click="$emit('close')">
                    {{ cancelText }}
                </SecondaryButton>
                <DangerButton @click="$emit('confirm')">
                    {{ confirmText }}
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>

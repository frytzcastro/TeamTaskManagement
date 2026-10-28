<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm',
    },
    message: {
        type: String,
        default: 'Are you sure you want to continue?',
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    cancelLabel: {
        type: String,
        default: 'Cancel',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'danger'].includes(v),
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-ink">{{ title }}</h3>
            <p class="mt-2 text-sm text-ink-muted">
                <slot>{{ message }}</slot>
            </p>
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <SecondaryButton type="button" :disabled="processing" @click="emit('close')">
                    {{ cancelLabel }}
                </SecondaryButton>
                <DangerButton
                    v-if="variant === 'danger'"
                    type="button"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </DangerButton>
                <PrimaryButton
                    v-else
                    type="button"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

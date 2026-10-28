<script setup>
defineProps({
    title: {
        type: String,
        default: 'Nothing here yet',
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-4 py-10 text-center">
        <p class="text-sm font-semibold tracking-tight text-ink">{{ title }}</p>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-ink-muted">{{ description }}</p>
        <div v-if="$slots.actions" class="mt-4 flex flex-wrap justify-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

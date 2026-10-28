<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    hint: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="app-card p-4">
        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">{{ label }}</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-ink">{{ value }}</p>
        <p v-if="hint || $slots.hint" class="mt-1 text-xs text-ink-muted">
            <slot name="hint">{{ hint }}</slot>
        </p>
    </div>
</template>

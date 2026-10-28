<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0 space-y-2">
            <div v-if="$slots.breadcrumbs">
                <slot name="breadcrumbs" />
            </div>
            <h1 class="text-[1.75rem] font-semibold leading-tight tracking-tight text-ink sm:text-[2rem]">
                {{ title }}
            </h1>
            <p v-if="description || $slots.description" class="max-w-2xl text-sm leading-relaxed text-ink-muted">
                <slot name="description">{{ description }}</slot>
            </p>
            <div v-if="$slots.meta" class="flex flex-wrap items-center gap-x-3 gap-y-1.5 pt-0.5 text-sm text-ink-muted">
                <slot name="meta" />
            </div>
        </div>
        <div v-if="$slots.actions" class="flex flex-shrink-0 flex-wrap items-center gap-2.5">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md'].includes(v),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center rounded-control border border-border bg-surface font-semibold text-ink transition duration-fast ease-out hover:border-border hover:bg-app hover:shadow-soft active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        :class="size === 'sm' ? 'px-3 py-1.5 text-sm' : 'px-4 py-2 text-sm'"
    >
        <slot />
    </button>
</template>

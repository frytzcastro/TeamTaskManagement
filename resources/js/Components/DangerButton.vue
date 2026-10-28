<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
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
        class="inline-flex items-center justify-center rounded-control border border-transparent bg-danger font-semibold text-white shadow-soft transition duration-fast ease-out hover:bg-red-700 hover:shadow-lift active:translate-y-px active:shadow-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-danger focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
        :class="size === 'sm' ? 'px-3 py-1.5 text-sm' : 'px-4 py-2.5 text-sm'"
    >
        <slot />
    </button>
</template>

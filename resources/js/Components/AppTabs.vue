<script setup>
defineProps({
    tabs: {
        type: Array,
        required: true,
        /** [{ id, label }] */
    },
    modelValue: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="-mb-px flex gap-1 overflow-x-auto overscroll-x-contain border-b border-border touch-pan-x" role="tablist">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            role="tab"
            :aria-selected="modelValue === tab.id"
            class="min-h-11 whitespace-nowrap border-b-2 px-3.5 py-2.5 text-sm font-medium transition duration-fast ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1"
            :class="
                modelValue === tab.id
                    ? 'border-brand text-brand'
                    : 'border-transparent text-ink-muted hover:border-slate-300 hover:text-ink'
            "
            @click="emit('update:modelValue', tab.id)"
        >
            {{ tab.label }}
        </button>
    </div>
</template>

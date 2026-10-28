<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    padding: {
        type: Boolean,
        default: true,
    },
    flush: {
        type: Boolean,
        default: false,
    },
    /**
     * card — bordered surface (default)
     * plain — open section, no box (title + content only)
     * soft — borderless with subtle shadow
     */
    variant: {
        type: String,
        default: 'card',
        validator: (v) => ['card', 'plain', 'soft'].includes(v),
    },
});
</script>

<template>
    <section
        :class="{
            'app-card overflow-hidden': variant === 'card',
            'app-card-soft overflow-hidden': variant === 'soft',
            'app-section': variant === 'plain',
        }"
    >
        <div
            v-if="title || $slots.header || $slots.actions"
            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            :class="variant === 'plain'
                ? 'pb-1'
                : 'border-b border-border/70 px-4 py-3 sm:px-5'"
        >
            <div class="min-w-0">
                <h2 v-if="title" class="app-section-title">{{ title }}</h2>
                <slot name="header" />
            </div>
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <div :class="flush ? '' : padding ? (variant === 'plain' ? '' : 'p-4 sm:p-5') : ''">
            <slot />
        </div>
        <div
            v-if="$slots.footer"
            class="px-4 py-3 sm:px-5"
            :class="variant === 'plain' ? 'border-t border-border/60 pt-3' : 'border-t border-border/70 bg-app/40'"
        >
            <slot name="footer" />
        </div>
    </section>
</template>

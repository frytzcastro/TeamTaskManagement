<script setup>
defineProps({
    /** When true, renders only the overflow wrapper + table slot (custom thead/tbody). */
    bare: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="-mx-px overflow-x-auto overscroll-x-contain">
        <table
            v-if="!bare"
            class="min-w-full divide-y divide-border"
            :class="$slots.mobile ? 'hidden sm:table' : ''"
        >
            <thead v-if="$slots.head" class="bg-app/80">
                <slot name="head" />
            </thead>
            <tbody class="divide-y divide-border bg-surface">
                <slot />
            </tbody>
        </table>
        <div v-else :class="$slots.mobile ? 'hidden sm:block' : ''">
            <slot />
        </div>
        <div v-if="$slots.mobile" class="divide-y divide-border sm:hidden">
            <slot name="mobile" />
        </div>
    </div>
</template>

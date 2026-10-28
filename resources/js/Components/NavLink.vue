<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex h-full items-center border-b-2 border-brand px-1 pt-1 text-sm font-medium leading-5 text-ink focus:outline-none focus-visible:border-brand-hover transition duration-150 ease-in-out'
        : 'inline-flex h-full items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-ink-muted hover:border-slate-300 hover:text-ink focus:outline-none focus-visible:border-slate-300 focus-visible:text-ink transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>

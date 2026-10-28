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
        ? 'block w-full border-l-4 border-brand bg-brand-soft py-2 pe-4 ps-3 text-start text-base font-medium text-brand focus:outline-none focus-visible:bg-blue-100 transition duration-150 ease-in-out'
        : 'block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-ink-muted hover:border-slate-300 hover:bg-slate-50 hover:text-ink focus:outline-none focus-visible:bg-slate-50 focus-visible:text-ink transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>

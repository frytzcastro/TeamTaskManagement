<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2.5 text-start text-sm leading-5 text-ink transition duration-fast ease-out hover:bg-brand-soft hover:text-brand focus:bg-brand-soft focus:text-brand focus:outline-none"
    >
        <slot />
    </Link>
</template>

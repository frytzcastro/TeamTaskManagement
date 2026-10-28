<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: {
        type: Array,
        required: true,
        /** [{ label, href? }] */
    },
});
</script>

<template>
    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1.5 text-sm text-ink-muted">
        <template v-for="(item, i) in items" :key="i">
            <span v-if="i > 0" class="text-slate-300" aria-hidden="true">/</span>
            <Link
                v-if="item.href"
                :href="item.href"
                class="hover:text-brand focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
            >
                {{ item.label }}
            </Link>
            <span v-else class="font-medium text-ink" aria-current="page">{{ item.label }}</span>
        </template>
    </nav>
</template>

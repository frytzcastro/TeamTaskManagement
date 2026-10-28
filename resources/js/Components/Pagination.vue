<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    /** Laravel paginator object with `links` array, or raw links array. */
    links: {
        type: [Array, Object],
        default: null,
    },
    meta: {
        type: Object,
        default: null,
    },
});

const items = computed(() => {
    if (Array.isArray(props.links)) return props.links;
    if (props.links?.links) return props.links.links;
    if (props.meta?.links) return props.meta.links;
    return [];
});

const show = computed(() => items.value.length > 3);
</script>

<template>
    <nav v-if="show" class="flex flex-wrap items-center justify-center gap-1" aria-label="Pagination">
        <template v-for="(link, i) in items" :key="i">
            <component
                :is="link.url ? Link : 'span'"
                :href="link.url || undefined"
                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-control px-3 text-sm transition duration-fast ease-out"
                :class="[
                    link.active
                        ? 'bg-brand font-medium text-white shadow-soft'
                        : link.url
                            ? 'border border-border bg-surface text-ink hover:bg-app'
                            : 'border border-transparent text-ink-muted',
                ]"
                v-html="link.label"
            />
        </template>
    </nav>
</template>

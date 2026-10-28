<script setup>
import { computed } from 'vue';

const props = defineProps({
    people: {
        type: Array,
        default: () => [],
    },
    max: {
        type: Number,
        default: 4,
    },
});

const initials = (name) => {
    if (!name) return '?';
    const parts = String(name).trim().split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const visible = computed(() => (props.people || []).slice(0, props.max));
const overflow = computed(() => Math.max(0, (props.people || []).length - props.max));
</script>

<template>
    <div v-if="people?.length" class="flex items-center" :title="people.map((p) => p.name).filter(Boolean).join(', ')">
        <span
            v-for="(person, index) in visible"
            :key="person.id || person.name || index"
            class="inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-surface bg-brand-soft text-[11px] font-semibold text-brand shadow-soft"
            :class="index > 0 ? '-ms-2' : ''"
            :style="{ zIndex: visible.length - index }"
        >
            {{ initials(person.name) }}
        </span>
        <span
            v-if="overflow > 0"
            class="-ms-2 inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-surface bg-app text-[11px] font-semibold text-ink-muted shadow-soft"
        >
            +{{ overflow }}
        </span>
    </div>
</template>

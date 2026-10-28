<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import SidebarIcon from '@/Components/SidebarIcon.vue';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    nested: {
        type: Boolean,
        default: false,
    },
    /** Named outline icon from SidebarIcon */
    icon: {
        type: String,
        default: '',
    },
    /** Colored marker for project rows */
    marker: {
        type: String,
        default: '',
    },
});

const classes = computed(() => {
    const base = props.nested
        ? 'group relative flex min-h-9 items-center gap-2.5 rounded-lg py-2 pe-2.5 ps-3 text-[13px] transition duration-fast ease-out'
        : 'group relative flex min-h-10 items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-sm font-medium transition duration-fast ease-out';
    return props.active
        ? `${base} bg-brand-soft text-ink shadow-soft`
        : `${base} text-ink-muted hover:bg-white hover:text-ink hover:shadow-soft`;
});
</script>

<template>
    <Link :href="href" :class="classes">
        <span
            v-if="active"
            class="absolute inset-y-1.5 start-0 w-1 rounded-full bg-brand"
            aria-hidden="true"
        />
        <span
            v-if="marker"
            class="inline-flex h-2.5 w-2.5 shrink-0 rounded-full ring-2 ring-white"
            :style="{ backgroundColor: marker }"
            aria-hidden="true"
        />
        <span
            v-else-if="icon || $slots.icon"
            class="inline-flex h-5 w-5 shrink-0 items-center justify-center transition-colors duration-fast ease-out"
            :class="active ? 'text-brand' : 'text-ink-muted group-hover:text-ink'"
        >
            <slot name="icon">
                <SidebarIcon v-if="icon" :name="icon" />
            </slot>
        </span>
        <span class="min-w-0 flex-1 truncate" :class="active ? 'font-semibold text-ink' : ''">
            <slot />
        </span>
    </Link>
</template>

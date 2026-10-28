<script setup>
import { computed } from 'vue';
import { formatStatusLabel, statusBadgeClass } from '@/Lib/statusColors';

const props = defineProps({
    value: { type: String, default: '' },
});

const critical = new Set([
    'at_risk', 'off_track', 'blocked', 'qa_failed', 'red', 'critical',
    'failed', 'rolled_back', 'reopened', 'delayed', 'slipped',
]);

const classes = computed(() => statusBadgeClass(props.value));
const label = computed(() => formatStatusLabel(props.value));
const isCritical = computed(() => critical.has(String(props.value || '').toLowerCase()));
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize tracking-wide ring-1 ring-inset"
        :class="[
            classes,
            isCritical ? 'ring-current/25 shadow-sm' : 'ring-black/[0.04]',
        ]"
    >
        <span
            class="h-1.5 w-1.5 shrink-0 rounded-full bg-current"
            :class="isCritical ? 'opacity-100' : 'opacity-80'"
            aria-hidden="true"
        />
        {{ label }}
    </span>
</template>

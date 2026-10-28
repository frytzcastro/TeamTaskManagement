<script setup>
import { CHART_COLORS } from '@/Lib/statusColors.js';
import EmptyState from '@/Components/EmptyState.vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Gantt from 'frappe-gantt';
import 'frappe-gantt/dist/frappe-gantt.css';

const props = defineProps({
    bars: { type: Array, default: () => [] },
    viewMode: { type: String, default: 'Month' },
});

const host = ref(null);
const error = ref('');
let gantt = null;
const metaById = new Map();

const colorBrand = CHART_COLORS.brand;
const colorSuccess = CHART_COLORS.success;
const colorWarning = CHART_COLORS.warning;
const colorInfo = CHART_COLORS.info;
const colorMuted = CHART_COLORS.muted;
const colorOverlap = CHART_COLORS.overlap;

const typePrefix = {
    project: 'P',
    epic: 'E',
    feature: 'F',
    task: 'T',
};

/** frappe-gantt treats end as exclusive; same-day bars need end = start + 1 day. */
const exclusiveEnd = (start, end) => {
    if (!start || !end) return end;
    if (end > start) return end;
    const d = new Date(`${start}T00:00:00`);
    d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
};

const ganttTasks = computed(() => {
    metaById.clear();
    return (props.bars || [])
        .filter((b) => b.display_start && b.display_end)
        .map((b) => {
            metaById.set(b.id, b);
            const indent = ' '.repeat((b.depth || 0) * 2);
            const flags = [
                b.slip || b.overdue ? 'slip' : null,
                b.overlap ? 'overlap' : null,
            ].filter(Boolean).join(' ');
            const classes = [
                `bar-type-${b.type}`,
                b.slip || b.overdue ? 'bar-slip' : '',
                b.overlap ? 'bar-overlap' : '',
            ].filter(Boolean).join(' ');

            return {
                id: b.id,
                name: `${indent}[${typePrefix[b.type] || '?'}] ${b.title}${flags ? ` (${flags})` : ''}`,
                start: b.display_start,
                end: exclusiveEnd(b.display_start, b.display_end),
                progress: b.type === 'task' && ['done', 'released'].includes(b.status) ? 100 : 0,
                custom_class: classes,
            };
        });
});

const render = () => {
    error.value = '';
    if (!host.value) return;
    host.value.innerHTML = '';
    gantt = null;
    if (!ganttTasks.value.length) return;

    try {
        gantt = new Gantt(host.value, ganttTasks.value, {
            view_mode: props.viewMode,
            language: 'en',
            custom_popup_html(task) {
                const m = metaById.get(task.id) || {};
                return `
                    <div class="p-2 text-xs">
                        <div class="font-semibold mb-1">${m.title || task.name}</div>
                        <div>Type: ${m.type || '—'}</div>
                        <div>Planned: ${m.planned_start || '—'} → ${m.planned_end || '—'}</div>
                        <div>Rolled: ${m.rolled_start || '—'} → ${m.rolled_end || '—'}</div>
                        <div>Status: ${m.status || '—'}</div>
                        ${m.assignee_name ? `<div>Assignee: ${m.assignee_name}</div>` : ''}
                        ${m.slip || m.overdue ? '<div class="text-amber-700">Schedule slip / overdue</div>' : ''}
                        ${m.overlap ? '<div class="text-violet-700">Assignee overlap</div>' : ''}
                    </div>
                `;
            },
            on_click(task) {
                const href = metaById.get(task.id)?.href;
                if (href) window.location.href = href;
            },
            on_date_change() {
                // read-only
            },
            on_progress_change() {
                // read-only
            },
        });
    } catch (e) {
        console.error('ScheduleGantt failed to render', e);
        error.value = e?.message || 'Failed to render schedule chart.';
    }
};

onMounted(render);
watch(() => [props.bars, props.viewMode], render, { deep: true });
onBeforeUnmount(() => {
    if (host.value) host.value.innerHTML = '';
    gantt = null;
});
</script>

<template>
    <div>
        <div v-if="error" class="rounded-lg bg-danger-soft p-4 text-sm text-danger">
            {{ error }}
        </div>
        <EmptyState
            v-else-if="!ganttTasks.length"
            title="No dated schedule bars"
            description="Set task start/due dates or expand a project with dated work."
        />
        <div v-else class="overflow-x-auto overscroll-x-contain p-3 touch-pan-x schedule-gantt-wrap">
            <div ref="host" class="gantt-target min-w-[40rem]" />
        </div>
    </div>
</template>

<style>
.schedule-gantt-wrap .bar-slip .bar-progress,
.schedule-gantt-wrap .bar-slip .bar {
    fill: v-bind(colorWarning) !important;
}
.schedule-gantt-wrap .bar-overlap .bar {
    stroke: v-bind(colorOverlap) !important;
    stroke-width: 2px;
}
.schedule-gantt-wrap .bar-type-project .bar {
    fill: v-bind(colorBrand);
}
.schedule-gantt-wrap .bar-type-epic .bar {
    fill: v-bind(colorInfo);
}
.schedule-gantt-wrap .bar-type-feature .bar {
    fill: v-bind(colorSuccess);
}
.schedule-gantt-wrap .bar-type-task .bar {
    fill: v-bind(colorMuted);
}
.schedule-gantt-wrap .bar-wrapper {
    cursor: pointer;
}
</style>

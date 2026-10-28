<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    columns: { type: Array, default: () => [] },
    wipLimit: { type: Number, default: 2 },
    showAssignee: { type: Boolean, default: false },
    /** total = count all in progress; perPerson = any assignee over limit */
    wipMode: { type: String, default: 'total' },
    reloadOnly: {
        type: Array,
        default: () => ['board', 'assigned', 'today', 'overdue', 'blocked', 'missingEstimate', 'missingDueDate', 'inProgressCount', 'attention'],
    },
});

const emit = defineEmits(['updated']);

const localColumns = ref([]);
const dragTaskId = ref(null);
const dragFromColumn = ref(null);
const errorMessage = ref('');
const estimatePrompt = ref(null); // { task, targetStatus, columnId }
const estimateInput = ref('');
const busy = ref(false);

watch(
    () => props.columns,
    (cols) => {
        localColumns.value = (cols || []).map((c) => ({
            ...c,
            tasks: [...(c.tasks || [])],
        }));
    },
    { immediate: true, deep: true },
);

const inProgressTasks = computed(() => {
    const col = localColumns.value.find((c) => c.id === 'in_progress');
    return col?.tasks || [];
});

const inProgressCount = computed(() => inProgressTasks.value.length);

const highWipPeople = computed(() => {
    const counts = {};
    for (const t of inProgressTasks.value) {
        const key = t.assignee_id || 'unassigned';
        if (!counts[key]) {
            counts[key] = { name: t.assignee?.name || 'Unassigned', count: 0 };
        }
        counts[key].count += 1;
    }
    return Object.values(counts).filter((row) => row.count > props.wipLimit);
});

const highWip = computed(() => {
    if (props.wipMode === 'perPerson') {
        return highWipPeople.value.length > 0;
    }
    return inProgressCount.value > props.wipLimit;
});

const wipMessage = computed(() => {
    if (props.wipMode === 'perPerson' && highWipPeople.value.length) {
        const names = highWipPeople.value.map((p) => `${p.name} (${p.count})`).join(', ');
        return `High WIP — ${names}. Soft limit ${props.wipLimit} In progress per person.`;
    }
    return `High WIP — ${inProgressCount.value} tasks In progress (soft limit ${props.wipLimit}). Finish or park as Blocked.`;
});

const findTask = (taskId) => {
    for (const col of localColumns.value) {
        const task = col.tasks.find((t) => t.id === taskId);
        if (task) return { task, column: col };
    }
    return null;
};

const onDragStart = (event, task, columnId) => {
    dragTaskId.value = task.id;
    dragFromColumn.value = columnId;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', task.id);
};

const onDragOver = (event) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
};

const primaryStatus = (column) => column.statuses?.[0] || column.id;

const moveLocally = (taskId, toColumnId) => {
    let task = null;
    for (const col of localColumns.value) {
        const idx = col.tasks.findIndex((t) => t.id === taskId);
        if (idx !== -1) {
            task = col.tasks.splice(idx, 1)[0];
            break;
        }
    }
    if (!task) return null;
    const target = localColumns.value.find((c) => c.id === toColumnId);
    if (!target) return null;
    task = { ...task, status: primaryStatus(target) };
    target.tasks.unshift(task);
    return task;
};

const restoreFromServer = () => {
    router.reload({ only: props.reloadOnly });
};

const patchStatus = async (task, status, estimate = null) => {
    busy.value = true;
    errorMessage.value = '';
    try {
        const payload = { status };
        if (estimate !== null && estimate !== '') {
            payload.estimate = estimate;
        }
        const { data } = await axios.patch(route('tasks.status.update', task.id), payload);
        const updated = data.task;
        // sync local card fields
        const found = findTask(task.id);
        if (found && updated) {
            Object.assign(found.task, {
                status: updated.status,
                estimate: updated.estimate,
                started_at: updated.started_at,
                completed_at: updated.completed_at,
            });
        }
        emit('updated', updated);
        return true;
    } catch (e) {
        const msg = e?.response?.data?.errors?.estimate?.[0]
            || e?.response?.data?.message
            || 'Could not update status.';
        errorMessage.value = msg;
        restoreFromServer();
        return false;
    } finally {
        busy.value = false;
    }
};

const onDrop = async (event, column) => {
    event.preventDefault();
    const taskId = dragTaskId.value || event.dataTransfer.getData('text/plain');
    dragTaskId.value = null;
    if (!taskId || dragFromColumn.value === column.id) return;

    const found = findTask(taskId);
    if (!found) return;

    const targetStatus = primaryStatus(column);
    const needsEstimate = targetStatus === 'in_progress'
        && (found.task.estimate === null || found.task.estimate === '' || Number(found.task.estimate) <= 0);

    if (needsEstimate) {
        estimatePrompt.value = { task: found.task, targetStatus, columnId: column.id };
        estimateInput.value = '';
        return;
    }

    moveLocally(taskId, column.id);
    await patchStatus(found.task, targetStatus);
};

const confirmEstimate = async () => {
    if (!estimatePrompt.value) return;
    const { task, targetStatus, columnId } = estimatePrompt.value;
    const estimate = Number(estimateInput.value);
    if (!estimate || estimate <= 0) {
        errorMessage.value = 'Enter a commitment estimate in working days.';
        return;
    }
    moveLocally(task.id, columnId);
    const ok = await patchStatus(task, targetStatus, estimate);
    if (ok) {
        estimatePrompt.value = null;
        estimateInput.value = '';
    }
};

const cancelEstimate = () => {
    estimatePrompt.value = null;
    estimateInput.value = '';
};

const isOverdue = (task) => {
    if (!task.due_date || ['done', 'released'].includes(task.status)) return false;
    return new Date(`${task.due_date}T23:59:59`) < new Date();
};
</script>

<template>
    <div class="space-y-3">
        <div
            v-if="highWip"
            class="rounded-md border border-warning/30 bg-warning-soft px-4 py-3 text-sm text-warning"
        >
            {{ wipMessage }}
        </div>
        <div
            v-if="errorMessage"
            class="rounded-md border border-danger/30 bg-danger-soft px-4 py-3 text-sm text-danger"
        >
            {{ errorMessage }}
            <button type="button" class="ml-2 underline" @click="errorMessage = ''">Dismiss</button>
        </div>

        <div
            v-if="estimatePrompt"
            class="rounded-lg border border-brand/30 bg-surface p-4 shadow-sm"
        >
            <div class="text-sm font-medium text-ink">
                Commitment estimate required for “{{ estimatePrompt.task.title }}”
            </div>
            <p class="mt-1 text-xs text-ink-muted">Working days before moving to In progress.</p>
            <div class="mt-3 flex flex-wrap items-end gap-3">
                <div>
                    <label class="text-xs text-ink-muted">Estimate (days)</label>
                    <TextInput
                        v-model="estimateInput"
                        type="number"
                        min="0.5"
                        step="0.5"
                        class="mt-1 w-32"
                        @keyup.enter="confirmEstimate"
                    />
                </div>
                <PrimaryButton
                    type="button"
                    size="sm"
                    :disabled="busy"
                    @click="confirmEstimate"
                >
                    Move to In progress
                </PrimaryButton>
                <SecondaryButton type="button" size="sm" @click="cancelEstimate">
                    Cancel
                </SecondaryButton>
            </div>
        </div>

        <div class="-mx-1 flex gap-3 overflow-x-auto overscroll-x-contain px-1 pb-2 touch-pan-x">
            <div
                v-for="column in localColumns"
                :key="column.id"
                class="flex w-[min(16.5rem,85vw)] shrink-0 flex-col rounded-card border border-border/80 bg-app/80"
                @dragover="onDragOver"
                @drop="onDrop($event, column)"
            >
                <div class="flex items-center justify-between px-3 py-2.5">
                    <span class="text-sm font-semibold tracking-tight text-ink">{{ column.label }}</span>
                    <span class="rounded-full bg-surface px-2 py-0.5 text-[11px] font-semibold text-ink-muted shadow-soft">{{ column.tasks.length }}</span>
                </div>
                <div class="min-h-[12rem] flex-1 space-y-2 p-2 pt-0">
                    <div
                        v-for="task in column.tasks"
                        :key="task.id"
                        draggable="true"
                        class="cursor-grab rounded-control border border-border/80 bg-surface p-3 shadow-soft transition hover:shadow-lift active:cursor-grabbing"
                        :class="{ 'opacity-60': busy && dragTaskId === task.id }"
                        @dragstart="onDragStart($event, task, column.id)"
                    >
                        <Link
                            :href="route('tasks.show', task.id)"
                            class="text-sm font-semibold tracking-tight text-ink hover:text-brand"
                            @click.stop
                        >
                            {{ task.title }}
                        </Link>
                        <div class="mt-1 text-xs text-ink-muted">
                            <span v-if="showAssignee" class="font-medium text-ink">{{ task.assignee?.name || 'Unassigned' }}</span>
                            <span v-if="showAssignee"> · </span>
                            {{ task.delivery_project?.name || '—' }}
                        </div>
                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5 text-xs">
                            <StatusBadge v-if="task.priority" :value="task.priority" />
                            <span
                                v-if="task.due_date"
                                :class="isOverdue(task) ? 'font-semibold text-danger' : 'text-ink-muted'"
                            >
                                Due {{ task.due_date }}
                            </span>
                            <span v-if="task.estimate" class="text-ink-muted">{{ task.estimate }}d</span>
                        </div>
                        <div v-if="task.blockers" class="mt-1 truncate text-xs text-warning">
                            Blocked: {{ task.blockers }}
                        </div>
                    </div>
                    <div v-if="!column.tasks.length" class="rounded-control border border-dashed border-border px-2 py-8 text-center text-xs text-ink-muted">
                        Drop here
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

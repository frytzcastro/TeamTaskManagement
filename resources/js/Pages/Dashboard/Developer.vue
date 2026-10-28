<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FilterBar from '@/Components/FilterBar.vue';
import StatCard from '@/Components/StatCard.vue';
import SectionCard from '@/Components/SectionCard.vue';
import DataTable from '@/Components/DataTable.vue';
import EmptyState from '@/Components/EmptyState.vue';
import TaskKanban from '@/Components/TaskKanban.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    assigned: Array,
    today: Array,
    overdue: Array,
    blocked: Array,
    missingEstimate: Array,
    missingDueDate: Array,
    openBugs: Array,
    kpis: Object,
    filters: Object,
    board: Array,
    wipLimit: { type: Number, default: 2 },
    inProgressCount: { type: Number, default: 0 },
    view: { type: String, default: 'board' },
});

const period = reactive({
    from: props.filters?.from || '',
    to: props.filters?.to || '',
});

const queryBase = () => ({
    from: period.from || undefined,
    to: period.to || undefined,
    view: props.view || 'board',
});

const applyPeriod = () => {
    router.get(route('dashboards.developer'), queryBase(), { preserveState: true });
};

const setView = (view) => {
    router.get(route('dashboards.developer'), { ...queryBase(), view }, { preserveState: true });
};

const formatScore = (value) => (value === null || value === undefined ? '—' : `${value}%`);

const onBoardUpdated = () => {
    router.reload({
        only: ['board', 'assigned', 'today', 'overdue', 'blocked', 'missingEstimate', 'missingDueDate', 'inProgressCount', 'kpis'],
    });
};
</script>

<template>
    <Head title="My Work" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="My Work">
                <template #actions>
                    <PrimaryButton v-if="view === 'board'" type="button" size="sm">Board</PrimaryButton>
                    <SecondaryButton v-else type="button" size="sm" @click="setView('board')">Board</SecondaryButton>
                    <PrimaryButton v-if="view === 'list'" type="button" size="sm">List</PrimaryButton>
                    <SecondaryButton v-else type="button" size="sm" @click="setView('list')">List</SecondaryButton>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard>
                    <form @submit.prevent="applyPeriod">
                        <FilterBar>
                            <div>
                                <InputLabel value="From" />
                                <TextInput v-model="period.from" type="date" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="To" />
                                <TextInput v-model="period.to" type="date" class="mt-1" />
                            </div>
                            <SecondaryButton type="submit">Update scores</SecondaryButton>
                            <p class="self-center text-xs text-ink-muted">Process KPIs for your owned work (not initiative business outcomes).</p>
                        </FilterBar>
                    </form>
                </SectionCard>

                <div class="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        label="Owned-work delivery"
                        :value="formatScore(kpis?.owned_delivery?.score)"
                        :hint="`${kpis?.owned_delivery?.scored_count || 0} scored items`"
                    />
                    <StatCard
                        label="Commitment accuracy"
                        :value="formatScore(kpis?.commitment?.score)"
                        :hint="`${kpis?.commitment?.scored_count || 0} completed with estimate`"
                    />
                    <StatCard
                        label="Quality (bug reopen)"
                        :value="formatScore(kpis?.quality?.score)"
                    >
                        <template #hint>
                            {{ kpis?.quality?.reopened ?? 0 }} reopened / {{ kpis?.quality?.resolved ?? 0 }} resolved
                        </template>
                    </StatCard>
                </div>

                <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    <StatCard label="Due today" :value="today.length" />
                    <StatCard label="Overdue" :value="overdue.length" />
                    <StatCard label="Blocked" :value="blocked.length" />
                    <StatCard label="Missing estimate" :value="missingEstimate?.length || 0" />
                    <StatCard label="Missing due date" :value="missingDueDate?.length || 0" />
                    <StatCard label="Open bugs" :value="openBugs?.length || 0" />
                </div>

                <TaskKanban
                    v-if="view === 'board'"
                    :columns="board"
                    :wip-limit="wipLimit"
                    @updated="onBoardUpdated"
                />

                <SectionCard v-else title="Assigned work" flush>
                    <DataTable v-if="assigned.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Work</th>
                                <th class="app-th">Delivery Project</th>
                                <th class="app-th">Due</th>
                                <th class="app-th">Estimate (days)</th>
                                <th class="app-th">Status</th>
                                <th class="app-th">Priority</th>
                            </tr>
                        </template>
                        <tr v-for="task in assigned" :key="task.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('tasks.show', task.id)" class="app-link font-medium">{{ task.title }}</Link>
                            </td>
                            <td class="app-td">
                                <Link
                                    v-if="task.delivery_project_id"
                                    :href="route('delivery-projects.show', task.delivery_project_id)"
                                    class="app-link"
                                >
                                    {{ task.delivery_project?.name }}
                                </Link>
                                <span v-else class="text-ink-muted">—</span>
                            </td>
                            <td class="app-td text-ink-muted">{{ task.due_date || '—' }}</td>
                            <td class="app-td text-ink-muted">{{ task.estimate ?? '—' }}</td>
                            <td class="app-td"><StatusBadge :value="task.status" /></td>
                            <td class="app-td capitalize text-ink-muted">{{ task.priority }}</td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No assigned open tasks" description="Nothing on your plate in this view." />
                </SectionCard>

                <SectionCard title="My open bugs">
                    <ul v-if="openBugs?.length" class="divide-y divide-border">
                        <li v-for="bug in openBugs" :key="bug.id" class="flex items-center justify-between gap-3 py-3 text-sm first:pt-0 last:pb-0">
                            <Link :href="route('bugs.show', bug.id)" class="app-link font-medium">{{ bug.title }}</Link>
                            <StatusBadge :value="bug.status" />
                        </li>
                    </ul>
                    <EmptyState v-else title="No open bugs assigned" description="You're clear on bugs for now." />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

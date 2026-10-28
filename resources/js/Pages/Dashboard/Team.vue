<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FilterBar from '@/Components/FilterBar.vue';
import StatCard from '@/Components/StatCard.vue';
import SectionCard from '@/Components/SectionCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import TaskKanban from '@/Components/TaskKanban.vue';
import { CHART_COLORS } from '@/Lib/statusColors.js';
import { Head, Link, router } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    metrics: Object,
    developerKpis: Object,
    teams: Array,
    users: Array,
    deliveryProjects: Array,
    filters: Object,
    view: { type: String, default: 'board' },
    board: Array,
    attention: Object,
    wipLimit: { type: Number, default: 2 },
    recentBugs: Array,
    overdueTasks: Array,
});

const ratesCanvas = ref(null);
const qualityCanvas = ref(null);
let ratesChart = null;
let qualityChart = null;

const applyFilter = (key, value) => {
    router.get(
        route('dashboards.team'),
        { ...props.filters, [key]: value || undefined },
        { preserveState: true },
    );
};

const setView = (view) => applyFilter('view', view);

const pct = (v) => (v === null || v === undefined ? '—' : `${v}%`);

const renderCharts = () => {
    if (props.view !== 'metrics') return;
    if (ratesCanvas.value) {
        ratesChart?.destroy();
        ratesChart = new Chart(ratesCanvas.value, {
            type: 'bar',
            data: {
                labels: ['Completion %', 'On-time %'],
                datasets: [{
                    label: 'Delivery rates',
                    data: [props.metrics.delivery.completion_rate || 0, props.metrics.delivery.on_time_rate || 0],
                    backgroundColor: [CHART_COLORS.brand, CHART_COLORS.success],
                }],
            },
            options: { scales: { y: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } },
        });
    }
    if (qualityCanvas.value) {
        qualityChart?.destroy();
        qualityChart = new Chart(qualityCanvas.value, {
            type: 'bar',
            data: {
                labels: ['Created', 'Resolved', 'Critical', 'Production'],
                datasets: [{
                    label: 'Bugs',
                    data: [
                        props.metrics.quality.bugs_created || 0,
                        props.metrics.quality.bugs_resolved || 0,
                        props.metrics.quality.open_critical || 0,
                        props.metrics.quality.production_bugs || 0,
                    ],
                    backgroundColor: [CHART_COLORS.info, CHART_COLORS.success, CHART_COLORS.danger, CHART_COLORS.warning],
                }],
            },
            options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } },
        });
    }
};

onMounted(() => nextTick(renderCharts));
watch(() => props.view, () => nextTick(renderCharts));

const onBoardUpdated = () => {
    router.reload({ only: ['board', 'attention', 'overdueTasks', 'metrics'] });
};

const filterSelectClass = 'rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand';
</script>

<template>
    <Head title="Team Delivery" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="Team Delivery"
                description="Flow board + computed metrics — separate from business KPI Outcomes."
            >
                <template #actions>
                    <PrimaryButton v-if="view === 'board'" type="button" size="sm">Board</PrimaryButton>
                    <SecondaryButton v-else type="button" size="sm" @click="setView('board')">Board</SecondaryButton>
                    <PrimaryButton v-if="view === 'metrics'" type="button" size="sm">Metrics</PrimaryButton>
                    <SecondaryButton v-else type="button" size="sm" @click="setView('metrics')">Metrics</SecondaryButton>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard>
                    <FilterBar>
                        <select :value="filters.team_id || ''" :class="filterSelectClass" @change="applyFilter('team_id', $event.target.value)">
                            <option value="">All teams</option>
                            <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <select :value="filters.user_id || ''" :class="filterSelectClass" @change="applyFilter('user_id', $event.target.value)">
                            <option value="">All users</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <select :value="filters.delivery_project_id || ''" :class="filterSelectClass" @change="applyFilter('delivery_project_id', $event.target.value)">
                            <option value="">All projects</option>
                            <option v-for="p in (deliveryProjects || [])" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <input type="date" :value="filters.from" :class="filterSelectClass" @change="applyFilter('from', $event.target.value)" />
                        <input type="date" :value="filters.to" :class="filterSelectClass" @change="applyFilter('to', $event.target.value)" />
                        <Link :href="route('schedule.index')" class="app-link self-center text-sm font-medium">Schedule →</Link>
                    </FilterBar>
                </SectionCard>

                <template v-if="view === 'board'">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard label="Overdue" :value="attention?.overdue?.length || 0" />
                        <StatCard label="Blocked" :value="attention?.blocked?.length || 0" />
                        <StatCard label="Missing estimate (Ready/IP)" :value="attention?.missing_estimate?.length || 0" />
                        <StatCard label="People over WIP" :value="attention?.high_wip_assignees?.length || 0" />
                    </div>

                    <SectionCard title="Attention">
                        <ul v-if="(attention?.overdue?.length || attention?.blocked?.length)" class="flex max-h-40 flex-wrap gap-x-6 gap-y-2 overflow-y-auto text-sm">
                            <li v-for="t in (attention?.overdue || []).slice(0, 6)" :key="'o'+t.id">
                                <Link :href="route('tasks.show', t.id)" class="app-link font-medium">{{ t.title }}</Link>
                                <span class="text-xs text-danger"> · Overdue · {{ t.assignee?.name || 'Unassigned' }}</span>
                            </li>
                            <li v-for="t in (attention?.blocked || []).slice(0, 6)" :key="'b'+t.id">
                                <Link :href="route('tasks.show', t.id)" class="app-link font-medium">{{ t.title }}</Link>
                                <span class="text-xs text-warning"> · Blocked · {{ t.assignee?.name || 'Unassigned' }}</span>
                            </li>
                        </ul>
                        <EmptyState v-else title="All clear" description="No overdue or blocked items in this filter." />
                    </SectionCard>

                    <TaskKanban
                        :columns="board"
                        :wip-limit="wipLimit"
                        wip-mode="perPerson"
                        show-assignee
                        :reload-only="['board', 'attention', 'overdueTasks']"
                        @updated="onBoardUpdated"
                    />
                </template>

                <template v-else>
                    <div v-if="developerKpis && filters.user_id">
                        <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-ink-muted">Developer process KPIs</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <StatCard label="Owned-work delivery" :value="pct(developerKpis.owned_delivery?.score)" />
                            <StatCard label="Commitment accuracy" :value="pct(developerKpis.commitment?.score)" />
                            <StatCard label="Quality" :value="pct(developerKpis.quality?.score)" />
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-ink-muted">Delivery</h3>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                            <StatCard label="Completed" :value="metrics.delivery.tasks_completed" />
                            <StatCard label="Overdue" :value="metrics.delivery.tasks_overdue" />
                            <StatCard label="Completion rate" :value="pct(metrics.delivery.completion_rate)" />
                            <StatCard label="On-time rate" :value="pct(metrics.delivery.on_time_rate)" />
                            <StatCard label="Avg cycle (hrs)" :value="metrics.delivery.avg_cycle_hours ?? '—'" />
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-ink-muted">Quality</h3>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                            <StatCard label="Bugs created" :value="metrics.quality.bugs_created" />
                            <StatCard label="Bugs resolved" :value="metrics.quality.bugs_resolved" />
                            <StatCard label="Open critical" :value="metrics.quality.open_critical" />
                            <StatCard label="Reopen rate" :value="pct(metrics.quality.reopen_rate)" />
                            <StatCard label="Production bugs" :value="metrics.quality.production_bugs" />
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <SectionCard title="Delivery rates">
                            <canvas ref="ratesCanvas" height="140" />
                        </SectionCard>
                        <SectionCard title="Quality snapshot">
                            <canvas ref="qualityCanvas" height="140" />
                        </SectionCard>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <SectionCard title="Overdue tasks">
                            <template #actions>
                                <Link :href="route('tasks.index')" class="app-link text-sm font-medium">View tasks</Link>
                            </template>
                            <ul v-if="overdueTasks.length" class="divide-y divide-border">
                                <li v-for="t in overdueTasks" :key="t.id" class="flex justify-between gap-2 py-3 text-sm first:pt-0 last:pb-0">
                                    <Link :href="route('tasks.show', t.id)" class="app-link font-medium">{{ t.title }}</Link>
                                    <span class="text-ink-muted">{{ t.assignee?.name || 'Unassigned' }} · {{ t.due_date }}</span>
                                </li>
                            </ul>
                            <EmptyState v-else title="None overdue" />
                        </SectionCard>
                        <SectionCard title="Recent bugs">
                            <template #actions>
                                <Link :href="route('bugs.index')" class="app-link text-sm font-medium">View bugs</Link>
                            </template>
                            <ul v-if="recentBugs.length" class="divide-y divide-border">
                                <li v-for="b in recentBugs" :key="b.id" class="flex items-center justify-between gap-2 py-3 text-sm first:pt-0 last:pb-0">
                                    <Link :href="route('bugs.show', b.id)" class="app-link font-medium">{{ b.title }}</Link>
                                    <div class="flex items-center gap-2">
                                        <StatusBadge :value="b.severity" />
                                        <StatusBadge :value="b.status" />
                                    </div>
                                </li>
                            </ul>
                            <EmptyState v-else title="No bugs" description="No recent bugs in this filter." />
                        </SectionCard>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

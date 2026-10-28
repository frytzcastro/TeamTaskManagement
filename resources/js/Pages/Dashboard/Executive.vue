<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import SectionCard from '@/Components/SectionCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    initiatives: Array,
    deliveryProjects: Array,
    delayedDeliveryProjects: Array,
    blockedTasks: Array,
    kpis: Array,
    stats: Object,
});
</script>

<template>
    <Head title="Executive Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Executive Dashboard" description="Delivery health and KPI outcomes at a glance." />
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <StatCard label="Initiatives" :value="stats.initiatives" />
                    <StatCard label="On track" :value="stats.delivery_projects_on_track" />
                    <StatCard label="At risk" :value="stats.delivery_projects_at_risk" />
                    <StatCard label="Open tasks" :value="stats.open_tasks" />
                    <StatCard label="Blocked" :value="stats.blocked_tasks" />
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <SectionCard title="KPI outcomes" flush>
                        <ul v-if="kpis?.length" class="divide-y divide-border">
                            <li v-for="kpi in kpis" :key="kpi.id" class="px-4 py-3 sm:px-6">
                                <div class="flex items-center justify-between gap-3">
                                    <Link :href="route('kpis.show', kpi.id)" class="app-link font-medium">{{ kpi.name }}</Link>
                                    <StatusBadge :value="kpi.status" />
                                </div>
                                <div class="text-sm text-ink-muted">
                                    {{ kpi.current_value ?? '—' }} / target {{ kpi.target ?? '—' }} · {{ kpi.trend || 'no trend' }}
                                </div>
                            </li>
                        </ul>
                        <EmptyState v-else title="No KPIs" description="Link KPI outcomes to initiatives to track business results." />
                    </SectionCard>

                    <SectionCard title="Delayed delivery projects" flush>
                        <ul v-if="delayedDeliveryProjects?.length" class="divide-y divide-border">
                            <li v-for="p in delayedDeliveryProjects" :key="p.id" class="px-4 py-3 sm:px-6">
                                <Link :href="route('delivery-projects.show', p.id)" class="app-link font-medium">{{ p.name }}</Link>
                                <div class="text-sm text-ink-muted">
                                    Target {{ p.target_completion }} · <StatusBadge :value="p.health" />
                                </div>
                            </li>
                        </ul>
                        <EmptyState v-else title="None delayed" description="All tracked projects are on schedule." />
                    </SectionCard>
                </div>

                <SectionCard title="Blocked work" flush>
                    <ul v-if="blockedTasks?.length" class="divide-y divide-border">
                        <li v-for="t in blockedTasks" :key="t.id" class="flex flex-wrap justify-between gap-2 px-4 py-3 text-sm sm:px-6">
                            <Link :href="route('tasks.show', t.id)" class="app-link font-medium">{{ t.title }}</Link>
                            <span class="text-ink-muted">{{ t.delivery_project?.name }} · {{ t.assignee?.name || 'Unassigned' }}</span>
                        </li>
                    </ul>
                    <EmptyState v-else title="No blocked tasks" />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

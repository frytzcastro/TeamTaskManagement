<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    pillars: Array,
    summary: Object,
});

const collapsed = ref({});

const togglePillar = (id) => {
    collapsed.value = { ...collapsed.value, [id]: !collapsed.value[id] };
};

const isCollapsed = (id) => !!collapsed.value[id];

const hasPillars = computed(() => (props.pillars || []).length > 0);

const kpiSignal = (kpi) => {
    const current = kpi.current_value ?? '—';
    const target = kpi.target ?? '—';
    return `${current} / ${target}`;
};
</script>

<template>
    <Head title="Portfolio" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="Portfolio"
                description="Strategic pillars → initiatives → delivery projects. Scan bets and outcomes, then drill into execution."
            >
                <template #meta>
                    <span>{{ summary?.pillars ?? 0 }} pillars</span>
                    <span class="text-slate-300" aria-hidden="true">·</span>
                    <span>{{ summary?.initiatives ?? 0 }} initiatives</span>
                    <span class="text-slate-300" aria-hidden="true">·</span>
                    <span>{{ summary?.delivery_projects ?? 0 }} projects</span>
                    <template v-if="summary?.at_risk_initiatives">
                        <span class="text-slate-300" aria-hidden="true">·</span>
                        <span class="text-warning">{{ summary.at_risk_initiatives }} at risk</span>
                    </template>
                </template>
                <template #actions>
                    <Link :href="route('pillars.index')" class="text-sm font-medium text-ink-muted hover:text-brand">Manage pillars</Link>
                    <Link :href="route('initiatives.index')" class="text-sm font-medium text-ink-muted hover:text-brand">All initiatives</Link>
                </template>
            </PageHeader>
        </template>

        <div class="pb-10 pt-2">
            <div class="app-page">
                <EmptyState
                    v-if="!hasPillars"
                    title="No strategic pillars yet"
                    description="Add pillars to organize the portfolio into themes, then attach initiatives and delivery projects."
                >
                    <template #actions>
                        <Link :href="route('pillars.index')" class="app-link text-sm font-medium">Go to pillars</Link>
                    </template>
                </EmptyState>

                <div v-else class="space-y-8">
                    <section
                        v-for="pillar in pillars"
                        :key="pillar.id"
                        class="border-b border-border/60 pb-8 last:border-b-0 last:pb-0"
                    >
                        <header class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <button
                                        type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-control text-ink-muted transition duration-fast ease-out hover:bg-app hover:text-ink"
                                        :aria-expanded="!isCollapsed(pillar.id)"
                                        :aria-label="isCollapsed(pillar.id) ? 'Expand pillar' : 'Collapse pillar'"
                                        @click="togglePillar(pillar.id)"
                                    >
                                        <svg class="h-4 w-4 transition duration-surface ease-out" :class="isCollapsed(pillar.id) ? '' : 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </button>
                                    <Link
                                        :href="route('pillars.show', pillar.id)"
                                        class="text-lg font-semibold tracking-tight text-ink hover:text-brand"
                                    >
                                        {{ pillar.name }}
                                    </Link>
                                    <StatusBadge :value="pillar.status" />
                                </div>
                                <p class="mt-1 ps-9 app-meta">
                                    {{ pillar.initiatives_count }} initiative{{ pillar.initiatives_count === 1 ? '' : 's' }}
                                </p>
                            </div>
                        </header>

                        <div v-show="!isCollapsed(pillar.id)" class="mt-5 ps-0 sm:ps-9">
                            <EmptyState
                                v-if="!pillar.initiatives?.length"
                                title="No initiatives under this pillar"
                                description="Create an initiative to turn this theme into executable bets."
                            />

                            <ul v-else class="divide-y divide-border/70 overflow-hidden rounded-card border border-border/70">
                                <li
                                    v-for="initiative in pillar.initiatives"
                                    :key="initiative.id"
                                    class="px-4 py-4 transition duration-surface ease-out hover:bg-app/60 sm:px-5"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <Link
                                                    :href="route('initiatives.show', initiative.id)"
                                                    class="font-semibold text-ink hover:text-brand"
                                                >
                                                    {{ initiative.name }}
                                                </Link>
                                                <StatusBadge :value="initiative.status" />
                                            </div>
                                            <p v-if="initiative.business_goal" class="mt-1 max-w-3xl text-sm text-ink-muted line-clamp-2">
                                                {{ initiative.business_goal }}
                                            </p>
                                        </div>
                                        <div class="app-meta shrink-0">
                                            {{ initiative.delivery_projects_count }} project{{ initiative.delivery_projects_count === 1 ? '' : 's' }}
                                        </div>
                                    </div>

                                    <!-- KPI outcomes -->
                                    <div v-if="initiative.kpis?.length" class="mt-3 flex flex-wrap gap-2">
                                        <Link
                                            v-for="kpi in initiative.kpis"
                                            :key="kpi.id"
                                            :href="route('kpis.show', kpi.id)"
                                            class="inline-flex items-center gap-2 rounded-control bg-app px-2.5 py-1.5 text-xs transition duration-fast ease-out hover:scale-[1.02] hover:bg-brand-soft hover:shadow-soft"
                                        >
                                            <span class="font-medium text-ink">{{ kpi.name }}</span>
                                            <span class="text-ink-muted">{{ kpiSignal(kpi) }}</span>
                                            <StatusBadge :value="kpi.status" />
                                        </Link>
                                    </div>
                                    <p v-else class="mt-2 app-meta">No KPI outcomes linked</p>

                                    <!-- Delivery projects -->
                                    <div v-if="initiative.delivery_projects?.length" class="mt-3 flex flex-wrap gap-x-4 gap-y-2">
                                        <Link
                                            v-for="project in initiative.delivery_projects"
                                            :key="project.id"
                                            :href="route('delivery-projects.show', project.id)"
                                            class="inline-flex items-center gap-2 text-sm text-ink-muted transition duration-fast ease-out hover:text-brand"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full"
                                                :class="{
                                                    'bg-success': project.health === 'green',
                                                    'bg-warning': project.health === 'amber',
                                                    'bg-danger': project.health === 'red',
                                                    'bg-slate-300': !['green', 'amber', 'red'].includes(project.health),
                                                }"
                                                aria-hidden="true"
                                            />
                                            <span class="font-medium text-ink">{{ project.name }}</span>
                                            <span v-if="project.code" class="app-meta">{{ project.code }}</span>
                                        </Link>
                                    </div>
                                    <p v-else class="mt-2 app-meta">No delivery projects yet</p>
                                </li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

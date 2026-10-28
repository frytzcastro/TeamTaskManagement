<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import SectionCard from '@/Components/SectionCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';
import { phaseLabel } from '@/Lib/improvementPhases';

defineProps({
    profiles: Array,
    byPhase: Object,
    openAlerts: Array,
    pendingGates: Array,
    stats: Object,
});
</script>

<template>
    <Head title="Improvement overview" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Improvement overview">
                <template #description>
                    Portfolio view of cycles. Open a project → <strong>Improve</strong> tab to run stages; use Plan / Build / Ship on that same project for fixes.
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <div class="grid gap-4 sm:grid-cols-3">
                    <StatCard label="Active" :value="stats.active" />
                    <StatCard label="Closed" :value="stats.closed" />
                    <StatCard label="Open alerts" :value="stats.open_alerts" />
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <SectionCard title="By stage" flush>
                        <ul class="divide-y divide-border">
                            <li v-for="(count, phase) in byPhase" :key="phase" class="flex justify-between px-4 py-3 text-sm sm:px-6">
                                <span class="text-ink">{{ phaseLabel(phase) }}</span>
                                <span class="font-medium text-ink">{{ count }}</span>
                            </li>
                        </ul>
                    </SectionCard>

                    <SectionCard title="Pending approvals" flush>
                        <ul v-if="pendingGates?.length" class="divide-y divide-border">
                            <li v-for="g in pendingGates" :key="g.id" class="px-4 py-3 text-sm sm:px-6">
                                <Link
                                    :href="route('delivery-projects.show', { deliveryProject: g.phase.profile.delivery_project_id || g.phase.profile.delivery_project?.id, tab: 'improve' })"
                                    class="app-link font-medium"
                                >
                                    {{ g.phase.profile.delivery_project?.name }} — {{ phaseLabel(g.phase.phase) }}
                                </Link>
                            </li>
                        </ul>
                        <EmptyState v-else title="None pending" />
                    </SectionCard>
                </div>

                <SectionCard title="Sustainment alerts" flush>
                    <ul v-if="openAlerts?.length" class="divide-y divide-border">
                        <li v-for="a in openAlerts" :key="a.id" class="flex justify-between gap-3 px-4 py-3 text-sm sm:px-6">
                            <span class="text-ink">{{ a.message }}</span>
                            <StatusBadge :value="a.severity" />
                        </li>
                    </ul>
                    <EmptyState v-else title="No open alerts" />
                </SectionCard>

                <SectionCard title="Improvement cycles" flush>
                    <template #actions>
                        <Link :href="route('dmaic.index')" class="app-link text-sm font-medium">Open list →</Link>
                    </template>
                    <ul v-if="profiles?.length" class="divide-y divide-border">
                        <li v-for="p in profiles" :key="p.id" class="flex justify-between gap-3 px-4 py-3 text-sm sm:px-6">
                            <Link
                                :href="route('delivery-projects.show', { deliveryProject: p.delivery_project_id || p.delivery_project?.id, tab: 'improve' })"
                                class="app-link font-medium"
                            >{{ p.delivery_project?.name }}</Link>
                            <span class="text-ink-muted">{{ phaseLabel(p.current_phase) }}</span>
                        </li>
                    </ul>
                    <EmptyState v-else title="No improvement cycles yet" />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

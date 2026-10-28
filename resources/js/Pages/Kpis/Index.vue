<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import DataTable from '@/Components/DataTable.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    kpis: Array,
    canManage: Boolean,
});
</script>

<template>
    <Head title="KPI Outcomes" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="KPI Outcomes" description="Business, process, and Six Sigma metrics linked to initiatives and delivery.">
                <template #actions>
                    <Link v-if="canManage" :href="route('kpis.create')">
                        <PrimaryButton type="button">New KPI</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard flush>
                    <DataTable v-if="kpis?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">KPI</th>
                                <th class="app-th">Baseline</th>
                                <th class="app-th">Target</th>
                                <th class="app-th">Current</th>
                                <th class="app-th">Status</th>
                            </tr>
                        </template>
                        <tr v-for="kpi in kpis" :key="kpi.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('kpis.show', kpi.id)" class="app-link font-medium">{{ kpi.name }}</Link>
                            </td>
                            <td class="app-td text-sm text-ink-muted">{{ kpi.baseline ?? '—' }}</td>
                            <td class="app-td text-sm text-ink-muted">{{ kpi.target ?? '—' }}</td>
                            <td class="app-td text-sm">{{ kpi.current_value ?? '—' }}</td>
                            <td class="app-td"><StatusBadge :value="kpi.status" /></td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No KPIs yet" description="Define outcomes to measure initiative success.">
                        <template v-if="canManage" #actions>
                            <Link :href="route('kpis.create')">
                                <PrimaryButton type="button">New KPI</PrimaryButton>
                            </Link>
                        </template>
                    </EmptyState>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

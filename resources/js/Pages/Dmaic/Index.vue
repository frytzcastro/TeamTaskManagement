<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import DataTable from '@/Components/DataTable.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';
import { phaseLabel } from '@/Lib/improvementPhases';

defineProps({ profiles: Object, canManage: Boolean });
</script>

<template>
    <Head title="Improvement cycles" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Improvement cycles">
                <template #description>
                    Structured improvement workspaces for delivery projects marked as
                    <span class="font-medium">structured improvement</span>.
                    Opens the project hub Improve tab. Fixes still ship via Plan → Build → Ship on that project.
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard flush>
                    <DataTable v-if="profiles.data?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Delivery project</th>
                                <th class="app-th">Stage</th>
                                <th class="app-th">Leader</th>
                                <th class="app-th">Status</th>
                            </tr>
                        </template>
                        <tr v-for="p in profiles.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link
                                    :href="route('delivery-projects.show', { deliveryProject: p.delivery_project_id || p.delivery_project?.id, tab: 'improve' })"
                                    class="app-link font-medium"
                                >{{ p.delivery_project?.name }}</Link>
                            </td>
                            <td class="app-td text-sm font-medium">{{ phaseLabel(p.current_phase) }}</td>
                            <td class="app-td text-sm text-ink-muted">{{ p.leader?.name || '—' }}</td>
                            <td class="app-td"><StatusBadge :value="p.status" /></td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No improvement cycles" description="Create a structured improvement project to start a cycle." />
                    <Pagination v-if="profiles.data?.length" :links="profiles.links" class="border-t border-border px-4 py-3 sm:px-6" />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

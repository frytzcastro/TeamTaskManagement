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
    deliveryProjects: Array,
    canManage: Boolean,
});
</script>

<template>
    <Head title="Delivery Projects" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Projects" description="Open a hub to plan, build, ship — or run an improvement cycle.">
                <template #actions>
                    <Link v-if="canManage" :href="route('delivery-projects.create')">
                        <PrimaryButton type="button">New project</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page">
                <SectionCard flush>
                    <DataTable v-if="deliveryProjects?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Project</th>
                                <th class="app-th">Type</th>
                                <th class="app-th">Initiative</th>
                                <th class="app-th">Health</th>
                                <th class="app-th">Tasks</th>
                                <th class="app-th"><span class="sr-only">Actions</span></th>
                            </tr>
                        </template>
                        <tr v-for="dp in deliveryProjects" :key="dp.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('delivery-projects.show', dp.id)" class="app-link font-medium">{{ dp.name }}</Link>
                            </td>
                            <td class="app-td">
                                <span
                                    class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium"
                                    :class="dp.type === 'dmaic' ? 'bg-warning-soft text-warning' : 'bg-slate-100 text-slate-700'"
                                >
                                    {{ dp.type === 'dmaic' ? 'Improvement' : 'Delivery' }}
                                </span>
                            </td>
                            <td class="app-td text-ink-muted">{{ dp.initiative?.name || '—' }}</td>
                            <td class="app-td"><StatusBadge :value="dp.health" /></td>
                            <td class="app-td">{{ dp.tasks_count }}</td>
                            <td class="app-td text-right">
                                <Link
                                    :href="route('delivery-projects.show', dp.type === 'dmaic' ? { deliveryProject: dp.id, tab: 'improve' } : dp.id)"
                                    class="app-link font-medium"
                                >Open hub</Link>
                            </td>
                        </tr>
                    </DataTable>
                    <EmptyState
                        v-else
                        title="No projects yet"
                        description="Create a delivery or improvement project to get started."
                    >
                        <template v-if="canManage" #actions>
                            <Link :href="route('delivery-projects.create')">
                                <PrimaryButton type="button">New project</PrimaryButton>
                            </Link>
                        </template>
                    </EmptyState>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

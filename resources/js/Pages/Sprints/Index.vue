<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FilterBar from '@/Components/FilterBar.vue';
import SectionCard from '@/Components/SectionCard.vue';
import DataTable from '@/Components/DataTable.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ sprints: Object, deliveryProjects: Array, filters: Object, canManage: Boolean });

const filter = (key, value) => {
    router.get(route('sprints.index'), { ...props.filters, [key]: value || undefined }, { preserveState: true });
};

const filterSelectClass = 'rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand';
</script>

<template>
    <Head title="Sprints" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Sprints" description="Time-box delivery project work. Tasks can link to an active sprint.">
                <template #actions>
                    <Link v-if="canManage" :href="route('sprints.create')">
                        <PrimaryButton type="button">New sprint</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard>
                    <FilterBar>
                        <select :value="filters.delivery_project_id || ''" :class="filterSelectClass" @change="filter('delivery_project_id', $event.target.value)">
                            <option value="">All projects</option>
                            <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </FilterBar>
                </SectionCard>

                <SectionCard flush>
                    <DataTable v-if="sprints.data?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Sprint</th>
                                <th class="app-th">Project</th>
                                <th class="app-th">Window</th>
                                <th class="app-th">Tasks</th>
                                <th class="app-th">Status</th>
                            </tr>
                        </template>
                        <tr v-for="s in sprints.data" :key="s.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('sprints.show', s.id)" class="app-link font-medium">{{ s.name }}</Link>
                            </td>
                            <td class="app-td text-ink-muted">{{ s.delivery_project?.name }}</td>
                            <td class="app-td text-ink-muted">{{ s.start_date || '—' }} → {{ s.end_date || '—' }}</td>
                            <td class="app-td text-ink-muted">{{ s.tasks_count }}</td>
                            <td class="app-td"><StatusBadge :value="s.status" /></td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No sprints yet" description="Adjust filters or create a sprint to time-box delivery work." />
                    <template v-if="sprints.data?.length" #footer>
                        <Pagination :links="sprints" />
                    </template>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

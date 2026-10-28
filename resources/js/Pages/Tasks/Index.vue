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

const props = defineProps({
    tasks: Object,
    deliveryProjects: Array,
    filters: Object,
    statuses: Array,
    canManage: Boolean,
});

const filter = (key, value) => {
    router.get(route('tasks.index'), { ...props.filters, [key]: value || undefined }, { preserveState: true });
};

const filterSelectClass = 'min-h-11 w-full rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand sm:min-h-0 sm:w-auto';
</script>

<template>
    <Head title="Tasks" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Tasks">
                <template #actions>
                    <Link v-if="canManage" :href="route('tasks.create')">
                        <PrimaryButton type="button">New Task</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard>
                    <FilterBar>
                        <select :value="filters.delivery_project_id || ''" :class="filterSelectClass" @change="filter('delivery_project_id', $event.target.value)">
                            <option value="">All delivery projects</option>
                            <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <select :value="filters.status || ''" :class="filterSelectClass" @change="filter('status', $event.target.value)">
                            <option value="">All statuses</option>
                            <option v-for="s in statuses" :key="s" :value="s">{{ s.replaceAll('_', ' ') }}</option>
                        </select>
                    </FilterBar>
                </SectionCard>

                <SectionCard flush>
                    <DataTable v-if="tasks.data?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Task</th>
                                <th class="app-th">Delivery Project</th>
                                <th class="app-th">Assignee</th>
                                <th class="app-th">Due</th>
                                <th class="app-th">Status</th>
                            </tr>
                        </template>
                        <tr v-for="task in tasks.data" :key="task.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('tasks.show', task.id)" class="app-link font-medium">{{ task.title }}</Link>
                            </td>
                            <td class="app-td text-ink-muted">{{ task.delivery_project?.name }}</td>
                            <td class="app-td text-ink-muted">{{ task.assignee?.name || '—' }}</td>
                            <td class="app-td text-ink-muted">{{ task.due_date || '—' }}</td>
                            <td class="app-td"><StatusBadge :value="task.status" /></td>
                        </tr>
                        <template #mobile>
                            <Link
                                v-for="task in tasks.data"
                                :key="`m-${task.id}`"
                                :href="route('tasks.show', task.id)"
                                class="block px-4 py-3 hover:bg-slate-50"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-medium text-ink">{{ task.title }}</div>
                                        <div class="mt-0.5 text-xs text-ink-muted">
                                            {{ task.delivery_project?.name || '—' }} · {{ task.assignee?.name || 'Unassigned' }}
                                            <span v-if="task.due_date"> · Due {{ task.due_date }}</span>
                                        </div>
                                    </div>
                                    <StatusBadge :value="task.status" />
                                </div>
                            </Link>
                        </template>
                    </DataTable>
                    <EmptyState v-else title="No tasks yet" description="Adjust filters or create a task to get started." />
                    <template v-if="tasks.data?.length" #footer>
                        <Pagination :links="tasks" />
                    </template>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

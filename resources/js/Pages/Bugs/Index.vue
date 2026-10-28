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
    bugs: Object,
    deliveryProjects: Array,
    users: Array,
    filters: Object,
    statuses: Array,
    severities: Array,
    canManage: Boolean,
    canReport: Boolean,
});

const filter = (key, value) => {
    router.get(route('bugs.index'), { ...props.filters, [key]: value || undefined }, { preserveState: true });
};

const filterSelectClass = 'min-h-11 w-full rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand sm:min-h-0 sm:w-auto';
</script>

<template>
    <Head title="Bugs" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Bugs">
                <template #actions>
                    <Link v-if="canReport" :href="route('bugs.create')">
                        <PrimaryButton type="button">Report Bug</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard>
                    <FilterBar>
                        <select :value="filters.status || ''" :class="filterSelectClass" @change="filter('status', $event.target.value)">
                            <option value="">All statuses</option>
                            <option v-for="s in statuses" :key="s" :value="s">{{ s.replaceAll('_', ' ') }}</option>
                        </select>
                        <select :value="filters.severity || ''" :class="filterSelectClass" @change="filter('severity', $event.target.value)">
                            <option value="">All severities</option>
                            <option v-for="s in severities" :key="s" :value="s">{{ s }}</option>
                        </select>
                        <select :value="filters.delivery_project_id || ''" :class="filterSelectClass" @change="filter('delivery_project_id', $event.target.value)">
                            <option value="">All delivery projects</option>
                            <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <select :value="filters.assignee_id || ''" :class="filterSelectClass" @change="filter('assignee_id', $event.target.value)">
                            <option value="">All assignees</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </FilterBar>
                </SectionCard>

                <SectionCard flush>
                    <DataTable v-if="bugs.data?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Bug</th>
                                <th class="app-th">Severity</th>
                                <th class="app-th">Status</th>
                                <th class="app-th">Project</th>
                                <th class="app-th">Assignee</th>
                            </tr>
                        </template>
                        <tr v-for="bug in bugs.data" :key="bug.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('bugs.show', bug.id)" class="app-link font-medium">{{ bug.title }}</Link>
                                <div class="text-xs capitalize text-ink-muted">{{ bug.environment }}</div>
                            </td>
                            <td class="app-td"><StatusBadge :value="bug.severity" /></td>
                            <td class="app-td"><StatusBadge :value="bug.status" /></td>
                            <td class="app-td text-ink-muted">{{ bug.delivery_project?.name || '—' }}</td>
                            <td class="app-td text-ink-muted">{{ bug.assignee?.name || '—' }}</td>
                        </tr>
                        <template #mobile>
                            <Link
                                v-for="bug in bugs.data"
                                :key="`m-${bug.id}`"
                                :href="route('bugs.show', bug.id)"
                                class="block px-4 py-3 hover:bg-slate-50"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-medium text-ink">{{ bug.title }}</div>
                                        <div class="mt-0.5 text-xs text-ink-muted">
                                            {{ bug.delivery_project?.name || '—' }} · {{ bug.assignee?.name || 'Unassigned' }}
                                        </div>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <StatusBadge :value="bug.severity" />
                                            <StatusBadge :value="bug.status" />
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </template>
                    </DataTable>
                    <EmptyState v-else title="No bugs yet" description="Adjust filters or report a bug when you find an issue." />
                    <template v-if="bugs.data?.length" #footer>
                        <Pagination :links="bugs" />
                    </template>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

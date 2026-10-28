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
    releases: Object,
    deliveryProjects: Array,
    filters: Object,
    canManage: Boolean,
});

const filter = (key, value) => {
    router.get(route('releases.index'), { ...props.filters, [key]: value || undefined }, { preserveState: true });
};

const filterSelectClass = 'rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand';
</script>

<template>
    <Head title="Releases" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Releases" description="Package features, tasks, and bugs for ship — then log deployments.">
                <template #actions>
                    <Link v-if="canManage" :href="route('releases.create')">
                        <PrimaryButton type="button">New release</PrimaryButton>
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
                    <DataTable v-if="releases.data?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Release</th>
                                <th class="app-th">Project</th>
                                <th class="app-th">Env</th>
                                <th class="app-th">Status</th>
                            </tr>
                        </template>
                        <tr v-for="r in releases.data" :key="r.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('releases.show', r.id)" class="app-link font-medium">{{ r.name }}</Link>
                                <div class="text-xs text-ink-muted">{{ r.version }}</div>
                            </td>
                            <td class="app-td text-ink-muted">{{ r.delivery_project?.name }}</td>
                            <td class="app-td text-ink-muted">{{ r.environment }}</td>
                            <td class="app-td"><StatusBadge :value="r.status" /></td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No releases yet" description="Adjust filters or create a release to package work for deployment." />
                    <template v-if="releases.data?.length" #footer>
                        <Pagination :links="releases" />
                    </template>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

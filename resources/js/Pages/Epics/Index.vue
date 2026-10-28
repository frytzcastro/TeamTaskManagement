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

const props = defineProps({ epics: Object, systems: Array, filters: Object, canManage: Boolean });

const filter = (key, value) => {
    router.get(route('epics.index'), { ...props.filters, [key]: value || undefined }, { preserveState: true });
};

const filterSelectClass = 'rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand';
</script>

<template>
    <Head title="Epics" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Epics" description="Themes on a system — features live under these, then get assigned into delivery projects.">
                <template #actions>
                    <Link v-if="canManage" :href="route('epics.create')">
                        <PrimaryButton type="button">New Epic</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard>
                    <FilterBar>
                        <select :value="filters.system_id || ''" :class="filterSelectClass" @change="filter('system_id', $event.target.value)">
                            <option value="">All systems</option>
                            <option v-for="s in systems" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </FilterBar>
                </SectionCard>

                <SectionCard flush>
                    <DataTable v-if="epics.data?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Epic</th>
                                <th class="app-th">System</th>
                                <th class="app-th">Status</th>
                                <th class="app-th">Features</th>
                            </tr>
                        </template>
                        <tr v-for="epic in epics.data" :key="epic.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('epics.show', epic.id)" class="app-link font-medium">{{ epic.title }}</Link>
                            </td>
                            <td class="app-td text-ink-muted">{{ epic.system?.name }}</td>
                            <td class="app-td"><StatusBadge :value="epic.status" /></td>
                            <td class="app-td text-sm">{{ epic.features_count }}</td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No epics yet" />
                    <Pagination v-if="epics.links" class="border-t border-border px-4 py-3" :links="epics.links" />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

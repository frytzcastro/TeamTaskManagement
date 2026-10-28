<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import DataTable from '@/Components/DataTable.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ problems: Object, canManage: Boolean });
</script>

<template>
    <Head title="Business Problems" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Business problems" description="Problems tied to initiatives that may drive improvement work.">
                <template #actions>
                    <Link v-if="canManage" :href="route('business-problems.create')">
                        <PrimaryButton type="button">New problem</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard flush>
                    <DataTable v-if="problems.data?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Problem</th>
                                <th class="app-th">Initiative</th>
                                <th class="app-th">Status</th>
                            </tr>
                        </template>
                        <tr v-for="p in problems.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('business-problems.show', p.id)" class="app-link font-medium">{{ p.title }}</Link>
                            </td>
                            <td class="app-td text-sm text-ink-muted">{{ p.initiative?.name }}</td>
                            <td class="app-td"><StatusBadge :value="p.status" /></td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No business problems" />
                    <Pagination v-if="problems.data?.length" :links="problems.links" class="border-t border-border px-4 py-3 sm:px-6" />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

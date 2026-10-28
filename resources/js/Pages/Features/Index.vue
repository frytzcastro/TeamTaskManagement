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

defineProps({ features: Object, canManage: Boolean });
</script>

<template>
    <Head title="Features" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Features" description="Shippable units of value under epics and delivery projects.">
                <template #actions>
                    <Link v-if="canManage" :href="route('features.create')">
                        <PrimaryButton type="button">New Feature</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard flush>
                    <DataTable v-if="features.data?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Feature</th>
                                <th class="app-th">Project</th>
                                <th class="app-th">Status</th>
                            </tr>
                        </template>
                        <tr v-for="f in features.data" :key="f.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('features.show', f.id)" class="app-link font-medium">{{ f.title }}</Link>
                            </td>
                            <td class="app-td text-ink-muted">{{ f.delivery_project?.name }}</td>
                            <td class="app-td"><StatusBadge :value="f.status" /></td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No features yet" description="Create a feature to track shippable work under an epic." />
                    <template v-if="features.data?.length" #footer>
                        <Pagination :links="features" />
                    </template>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

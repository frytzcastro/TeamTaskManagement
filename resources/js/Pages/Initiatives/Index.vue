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
    initiatives: Array,
    canManage: Boolean,
});
</script>

<template>
    <Head title="Initiatives" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Initiatives" description="Strategic initiatives under pillars, linked to delivery and KPI outcomes.">
                <template #actions>
                    <Link v-if="canManage" :href="route('initiatives.create')">
                        <PrimaryButton type="button">New initiative</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard flush>
                    <DataTable v-if="initiatives?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Initiative</th>
                                <th class="app-th">Pillar</th>
                                <th class="app-th">Owner</th>
                                <th class="app-th">Status</th>
                                <th class="app-th">Delivery projects</th>
                            </tr>
                        </template>
                        <tr v-for="item in initiatives" :key="item.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('initiatives.show', item.id)" class="app-link font-medium">
                                    {{ item.code ? item.code + ' — ' : '' }}{{ item.name }}
                                </Link>
                            </td>
                            <td class="app-td text-sm text-ink-muted">{{ item.strategic_pillar?.name }}</td>
                            <td class="app-td text-sm text-ink-muted">{{ item.owner?.name || '—' }}</td>
                            <td class="app-td"><StatusBadge :value="item.status" /></td>
                            <td class="app-td text-sm">{{ item.delivery_projects_count }}</td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No initiatives yet">
                        <template v-if="canManage" #actions>
                            <Link :href="route('initiatives.create')">
                                <PrimaryButton type="button">New initiative</PrimaryButton>
                            </Link>
                        </template>
                    </EmptyState>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

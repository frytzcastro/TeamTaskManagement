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
    systems: Array,
    canManage: Boolean,
});
</script>

<template>
    <Head title="Systems" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Systems" description="Apps, services, and platforms — each owns its epic and feature backlog.">
                <template #actions>
                    <Link v-if="canManage" :href="route('systems.create')">
                        <PrimaryButton type="button">Add system</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard flush>
                    <DataTable v-if="systems?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">System</th>
                                <th class="app-th">Type</th>
                                <th class="app-th">Owner</th>
                                <th class="app-th">Status</th>
                                <th class="app-th">Epics</th>
                                <th class="app-th">Features</th>
                            </tr>
                        </template>
                        <tr v-for="system in systems" :key="system.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('systems.show', system.id)" class="app-link font-medium">{{ system.name }}</Link>
                                <p v-if="system.code" class="text-sm text-ink-muted">{{ system.code }}</p>
                            </td>
                            <td class="app-td text-sm capitalize text-ink-muted">{{ system.type || '—' }}</td>
                            <td class="app-td text-sm text-ink-muted">{{ system.owner?.name || '—' }}</td>
                            <td class="app-td"><StatusBadge :value="system.status" /></td>
                            <td class="app-td text-sm">{{ system.epics_count }}</td>
                            <td class="app-td text-sm">{{ system.features_count }}</td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No systems yet">
                        <template v-if="canManage" #actions>
                            <Link :href="route('systems.create')">
                                <PrimaryButton type="button">Add system</PrimaryButton>
                            </Link>
                        </template>
                    </EmptyState>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

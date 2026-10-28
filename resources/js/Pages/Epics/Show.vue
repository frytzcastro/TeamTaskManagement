<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import SectionCard from '@/Components/SectionCard.vue';
import DataTable from '@/Components/DataTable.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ epic: Object, canManage: Boolean, canManageFeatures: Boolean });
</script>

<template>
    <Head :title="epic.title" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="epic.title">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Epics', href: route('epics.index') },
                        { label: epic.title },
                    ]" />
                </template>
                <template #meta>
                    <StatusBadge :value="epic.status" />
                </template>
                <template #actions>
                    <Link v-if="canManage" :href="route('epics.edit', epic.id)" class="inline-flex">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <SectionCard class="lg:col-span-2">
                        <p class="text-ink">{{ epic.description || 'No description.' }}</p>
                    </SectionCard>
                    <SectionCard title="Schedule">
                        <dl class="space-y-2 text-sm">
                            <div>
                                <span class="text-ink-muted">System:</span>
                                <Link v-if="epic.system" :href="route('systems.show', epic.system.id)" class="app-link ms-1">{{ epic.system.name }}</Link>
                                <span v-else> —</span>
                            </div>
                            <div><span class="text-ink-muted">Planned:</span> {{ epic.start_date || '—' }} → {{ epic.due_date || '—' }}</div>
                            <div><span class="text-ink-muted">Rolled envelope:</span> {{ epic.rolled_start_date || '—' }} → {{ epic.rolled_due_date || '—' }}</div>
                            <div
                                v-if="epic.due_date && epic.rolled_due_date && epic.rolled_due_date > epic.due_date"
                                class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
                            >
                                Children push past planned end
                            </div>
                        </dl>
                    </SectionCard>
                </div>

                <SectionCard flush title="Features">
                    <DataTable v-if="epic.features?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Feature</th>
                            </tr>
                        </template>
                        <tr v-for="f in epic.features" :key="f.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('features.show', f.id)" class="app-link font-medium">{{ f.title }}</Link>
                            </td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No features on this epic" description="Create or link features to this epic from the feature form." />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

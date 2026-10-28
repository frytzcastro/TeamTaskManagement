<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import SectionCard from '@/Components/SectionCard.vue';
import StatCard from '@/Components/StatCard.vue';
import DataTable from '@/Components/DataTable.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ sprint: Object, stats: Object, canManage: Boolean });
</script>

<template>
    <Head :title="sprint.name" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="sprint.name">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Sprints', href: route('sprints.index') },
                        { label: sprint.name },
                    ]" />
                </template>
                <template #meta>
                    <span class="text-sm text-ink-muted">{{ sprint.delivery_project?.name }}</span>
                    <StatusBadge :value="sprint.status" />
                </template>
                <template #actions>
                    <Link v-if="canManage" :href="route('sprints.edit', sprint.id)" class="inline-flex">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <p v-if="sprint.goal" class="text-sm text-ink-muted">{{ sprint.goal }}</p>

                <div class="grid gap-4 sm:grid-cols-3">
                    <StatCard label="Tasks done" :value="`${stats.done}/${stats.total}`" hint="Completed in this sprint" />
                    <StatCard label="Start" :value="sprint.start_date || '—'" />
                    <StatCard label="End" :value="sprint.end_date || '—'" />
                </div>

                <SectionCard flush title="Sprint tasks">
                    <DataTable v-if="sprint.tasks?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Task</th>
                                <th class="app-th">Assignee</th>
                                <th class="app-th">Status</th>
                            </tr>
                        </template>
                        <tr v-for="t in sprint.tasks" :key="t.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('tasks.show', t.id)" class="app-link font-medium">{{ t.title }}</Link>
                            </td>
                            <td class="app-td text-ink-muted">{{ t.assignee?.name || '—' }}</td>
                            <td class="app-td"><StatusBadge :value="t.status" /></td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No tasks linked yet" description="Link tasks to this sprint from the task detail view." />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

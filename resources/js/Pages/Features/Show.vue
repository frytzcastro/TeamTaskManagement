<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import SectionCard from '@/Components/SectionCard.vue';
import StatCard from '@/Components/StatCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    feature: Object,
    canManage: Boolean,
    canManageTasks: Boolean,
});

const dodItems = computed(() => props.feature.dod_checklist || []);
const dodCompleted = computed(() => dodItems.value.filter((i) => i.completed).length);
const dodTotal = computed(() => dodItems.value.length);
const dodPercent = computed(() => (dodTotal.value ? Math.round((dodCompleted.value / dodTotal.value) * 100) : 0));

const crumbs = computed(() => {
    if (props.feature.delivery_project) {
        return [
            { label: 'Projects', href: route('delivery-projects.index') },
            {
                label: props.feature.delivery_project.name,
                href: route('delivery-projects.show', {
                    deliveryProject: props.feature.delivery_project_id,
                    tab: 'plan',
                }),
            },
            { label: props.feature.title },
        ];
    }
    return [
        { label: 'Features', href: route('features.index') },
        { label: props.feature.title },
    ];
});

const addTaskHref = computed(() => route('tasks.create', {
    delivery_project_id: props.feature.delivery_project_id,
    feature_id: props.feature.id,
    return_feature_id: props.feature.id,
    return_project_id: props.feature.delivery_project_id,
}));
</script>

<template>
    <Head :title="feature.title" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="feature.title">
                <template #breadcrumbs>
                    <Breadcrumbs :items="crumbs" />
                </template>
                <template #meta>
                    <StatusBadge :value="feature.status" />
                </template>
                <template #actions>
                    <Link v-if="canManage" :href="route('features.edit', feature.id)" class="inline-flex">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <SectionCard class="lg:col-span-2" title="Overview">
                        <p class="text-ink">{{ feature.description || 'No description.' }}</p>
                        <div class="mt-4 rounded-md border border-border bg-slate-50 p-4 text-sm">
                            <h3 class="font-semibold text-ink">Acceptance criteria</h3>
                            <p class="mt-2 whitespace-pre-wrap text-ink-muted">{{ feature.acceptance_criteria || '—' }}</p>
                        </div>
                    </SectionCard>
                    <SectionCard title="Schedule">
                        <dl class="space-y-2 text-sm">
                            <div><span class="text-ink-muted">Planned:</span> {{ feature.start_date || '—' }} → {{ feature.due_date || '—' }}</div>
                            <div><span class="text-ink-muted">Rolled envelope:</span> {{ feature.rolled_start_date || '—' }} → {{ feature.rolled_due_date || '—' }}</div>
                            <div
                                v-if="feature.due_date && feature.rolled_due_date && feature.rolled_due_date > feature.due_date"
                                class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
                            >
                                Children push past planned end
                            </div>
                        </dl>
                    </SectionCard>
                </div>

                <SectionCard title="Build" flush>
                    <template v-if="canManageTasks" #actions>
                        <Link :href="addTaskHref">
                            <SecondaryButton type="button" size="sm">Add task</SecondaryButton>
                        </Link>
                    </template>
                    <ul v-if="feature.tasks?.length" class="divide-y divide-border">
                        <li
                            v-for="task in feature.tasks"
                            :key="task.id"
                            class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                        >
                            <Link :href="route('tasks.show', task.id)" class="app-link font-medium">{{ task.title }}</Link>
                            <div class="flex items-center gap-2 text-sm text-ink-muted">
                                <span>{{ task.assignee?.name || 'Unassigned' }}</span>
                                <StatusBadge :value="task.status" />
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else title="No tasks yet" description="Break this feature into assigned execution work.">
                        <template v-if="canManageTasks" #actions>
                            <Link :href="addTaskHref">
                                <PrimaryButton type="button" size="sm">Add task</PrimaryButton>
                            </Link>
                        </template>
                    </EmptyState>
                </SectionCard>

                <SectionCard title="Definition of Done">
                    <div v-if="dodTotal" class="mb-4 grid gap-4 sm:grid-cols-3">
                        <StatCard label="Checklist progress" :value="`${dodCompleted}/${dodTotal}`" :hint="`${dodPercent}% complete`" />
                        <div class="sm:col-span-2 flex flex-col justify-center">
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-brand transition-all"
                                    :style="{ width: `${dodPercent}%` }"
                                />
                            </div>
                        </div>
                    </div>
                    <ul v-if="dodTotal" class="space-y-2">
                        <li
                            v-for="item in dodItems"
                            :key="item.key"
                            class="flex items-start gap-3 rounded-md border px-4 py-3 text-sm"
                            :class="item.completed ? 'border-emerald-200 bg-emerald-50/80' : 'border-border bg-surface'"
                        >
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                                :class="item.completed ? 'bg-emerald-600 text-white' : 'border border-border text-ink-muted'"
                                aria-hidden="true"
                            >
                                {{ item.completed ? '✓' : '' }}
                            </span>
                            <span :class="item.completed ? 'text-ink line-through decoration-ink-muted/50' : 'text-ink'">{{ item.label }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-ink-muted">No checklist yet — edit feature to set DoD.</p>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

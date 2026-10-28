<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    initiative: Object,
    canManage: Boolean,
});
</script>

<template>
    <Head :title="initiative.name" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="(initiative.code ? initiative.code + ' — ' : '') + initiative.name">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Initiatives', href: route('initiatives.index') },
                        { label: initiative.name },
                    ]" />
                </template>
                <template #actions>
                    <Link v-if="canManage" :href="route('initiatives.edit', initiative.id)">
                        <PrimaryButton type="button">Edit</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <SectionCard class="lg:col-span-2" title="Context">
                        <div class="space-y-4 text-sm">
                            <div>
                                <h3 class="font-semibold text-ink">Business goal</h3>
                                <p class="mt-1 text-ink-muted">{{ initiative.business_goal || '—' }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-ink">Problem</h3>
                                <p class="mt-1 text-ink-muted">{{ initiative.problem || '—' }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-ink">Success criteria</h3>
                                <p class="mt-1 text-ink-muted">{{ initiative.success_criteria || '—' }}</p>
                            </div>
                        </div>
                    </SectionCard>
                    <SectionCard title="Summary">
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-ink-muted">Pillar</dt>
                                <dd class="font-medium text-ink">{{ initiative.strategic_pillar?.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-muted">Owner</dt>
                                <dd class="text-ink">{{ initiative.owner?.name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-muted">Status</dt>
                                <dd class="mt-1"><StatusBadge :value="initiative.status" /></dd>
                            </div>
                            <div>
                                <dt class="text-ink-muted">Priority</dt>
                                <dd class="capitalize text-ink">{{ initiative.priority }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-muted">Systems touched</dt>
                                <dd class="mt-1 flex flex-wrap gap-2">
                                    <Link
                                        v-for="s in initiative.systems || []"
                                        :key="s.id"
                                        :href="route('systems.show', s.id)"
                                        class="rounded-control bg-app px-2 py-0.5 text-sm font-medium text-ink hover:bg-brand-soft hover:text-brand"
                                    >{{ s.name }}</Link>
                                    <span v-if="!(initiative.systems || []).length" class="text-ink-muted">—</span>
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>
                </div>

                <SectionCard title="Delivery projects" flush>
                    <ul v-if="initiative.delivery_projects?.length" class="divide-y divide-border">
                        <li v-for="dp in initiative.delivery_projects" :key="dp.id" class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                            <Link :href="route('delivery-projects.show', dp.id)" class="app-link font-medium">{{ dp.name }}</Link>
                            <StatusBadge :value="dp.health" />
                        </li>
                    </ul>
                    <EmptyState v-else title="No delivery projects yet" />
                </SectionCard>

                <SectionCard title="KPI outcomes" flush>
                    <ul v-if="initiative.kpis?.length" class="divide-y divide-border">
                        <li v-for="kpi in initiative.kpis" :key="kpi.id" class="px-4 py-3 sm:px-6">
                            <Link :href="route('kpis.show', kpi.id)" class="app-link font-medium">{{ kpi.name }}</Link>
                            <div class="text-sm text-ink-muted">
                                Baseline {{ kpi.baseline ?? '—' }} → Target {{ kpi.target ?? '—' }} · Current {{ kpi.current_value ?? '—' }}
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else title="No KPIs linked" />
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

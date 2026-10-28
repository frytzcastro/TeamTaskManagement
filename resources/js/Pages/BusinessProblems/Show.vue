<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ problem: Object, canManage: Boolean });
</script>

<template>
    <Head :title="problem.title" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="problem.title">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Business problems', href: route('business-problems.index') },
                        { label: problem.title },
                    ]" />
                </template>
                <template #actions>
                    <Link v-if="canManage" :href="route('business-problems.edit', problem.id)">
                        <PrimaryButton type="button">Edit</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard title="Details">
                    <div class="space-y-3 text-sm">
                        <div><StatusBadge :value="problem.status" /></div>
                        <p><span class="font-medium text-ink">Statement:</span> <span class="text-ink-muted">{{ problem.problem_statement || '—' }}</span></p>
                        <p><span class="font-medium text-ink">Business impact:</span> <span class="text-ink-muted">{{ problem.business_impact || '—' }}</span></p>
                        <p class="text-ink-muted">
                            Initiative:
                            <Link
                                v-if="problem.initiative"
                                :href="route('initiatives.show', problem.initiative.id)"
                                class="app-link"
                            >{{ problem.initiative.name }}</Link>
                            <span v-else>—</span>
                        </p>
                    </div>
                </SectionCard>

                <SectionCard v-if="problem.dmaic_profiles?.length" title="Improvement projects" flush>
                    <ul class="divide-y divide-border">
                        <li
                            v-for="profile in problem.dmaic_profiles"
                            :key="profile.id"
                            class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-6"
                        >
                            <div>
                                <div class="font-medium text-ink">{{ profile.delivery_project?.name || 'DMAIC project' }}</div>
                                <div class="text-sm text-ink-muted">Phase {{ profile.current_phase || '—' }}</div>
                            </div>
                            <Link
                                v-if="profile.delivery_project"
                                :href="route('delivery-projects.show', { deliveryProject: profile.delivery_project.id, tab: 'improve' })"
                                class="app-link text-sm font-medium"
                            >Open Improve →</Link>
                        </li>
                    </ul>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

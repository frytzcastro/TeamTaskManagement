<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    system: Object,
    canManage: Boolean,
    canManageEpics: Boolean,
    canManageFeatures: Boolean,
});
</script>

<template>
    <Head :title="system.name" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="system.name" description="System backlog — epics and features owned here; assign features into delivery projects when ready to build.">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Systems', href: route('systems.index') },
                        { label: system.name },
                    ]" />
                </template>
                <template #actions>
                    <Link v-if="canManage" :href="route('systems.edit', system.id)">
                        <SecondaryButton type="button">Edit system</SecondaryButton>
                    </Link>
                    <Link v-if="canManageEpics" :href="route('epics.create', { system_id: system.id })">
                        <SecondaryButton type="button">Add epic</SecondaryButton>
                    </Link>
                    <Link v-if="canManageFeatures" :href="route('features.create', { system_id: system.id })">
                        <PrimaryButton type="button">Add feature</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard title="Overview">
                    <p class="text-sm text-ink">{{ system.description || 'No description.' }}</p>
                    <div class="mt-3 flex flex-wrap gap-4 text-sm text-ink-muted">
                        <span>Type: <span class="capitalize text-ink">{{ system.type }}</span></span>
                        <span>Owner: {{ system.owner?.name || '—' }}</span>
                        <StatusBadge :value="system.status" />
                    </div>
                </SectionCard>

                <SectionCard title="Initiatives touching this system" flush>
                    <ul v-if="system.initiatives?.length" class="divide-y divide-border">
                        <li v-for="init in system.initiatives" :key="init.id" class="px-4 py-3 sm:px-6">
                            <Link :href="route('initiatives.show', init.id)" class="app-link font-medium">{{ init.name }}</Link>
                        </li>
                    </ul>
                    <EmptyState v-else title="Not linked to any initiative yet" />
                </SectionCard>

                <SectionCard title="Epics" flush>
                    <ul v-if="system.epics?.length" class="divide-y divide-border">
                        <li v-for="epic in system.epics" :key="epic.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-6">
                            <div>
                                <Link :href="route('epics.show', epic.id)" class="app-link font-medium">{{ epic.title }}</Link>
                                <div class="text-sm text-ink-muted">{{ epic.features_count ?? 0 }} features</div>
                            </div>
                            <StatusBadge :value="epic.status" />
                        </li>
                    </ul>
                    <EmptyState v-else title="No epics yet">
                        <template v-if="canManageEpics" #actions>
                            <Link :href="route('epics.create', { system_id: system.id })">
                                <PrimaryButton type="button" size="sm">Add epic</PrimaryButton>
                            </Link>
                        </template>
                    </EmptyState>
                </SectionCard>

                <SectionCard title="Features" flush>
                    <ul v-if="system.features?.length" class="divide-y divide-border">
                        <li v-for="f in system.features" :key="f.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                <Link :href="route('features.show', f.id)" class="app-link font-medium">{{ f.title }}</Link>
                                <div class="text-sm text-ink-muted">
                                    <span v-if="f.epic">{{ f.epic.title }} · </span>
                                    <span v-if="f.delivery_project">{{ f.delivery_project.name }}</span>
                                    <span v-else class="italic">Backlog</span>
                                </div>
                            </div>
                            <StatusBadge :value="f.status" />
                        </li>
                    </ul>
                    <EmptyState v-else title="No features yet">
                        <template v-if="canManageFeatures" #actions>
                            <Link :href="route('features.create', { system_id: system.id })">
                                <PrimaryButton type="button" size="sm">Add feature</PrimaryButton>
                            </Link>
                        </template>
                    </EmptyState>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

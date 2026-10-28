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
import { computed } from 'vue';

const props = defineProps({
    pillar: Object,
    canManage: Boolean,
    canManageInitiatives: Boolean,
});

const createInitiativeHref = computed(() =>
    route('initiatives.create', {
        strategic_pillar_id: props.pillar.id,
        return_to_pillar: 1,
    }),
);
</script>

<template>
    <Head :title="pillar.name" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="pillar.name" description="Strategic theme and the initiatives that execute it.">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Strategic pillars', href: route('pillars.index') },
                        { label: pillar.name },
                    ]" />
                </template>
                <template #meta>
                    <StatusBadge :value="pillar.status" />
                    <span class="app-meta">{{ pillar.initiatives?.length || 0 }} initiative{{ (pillar.initiatives?.length || 0) === 1 ? '' : 's' }}</span>
                </template>
                <template v-if="canManageInitiatives" #actions>
                    <Link :href="createInitiativeHref">
                        <PrimaryButton type="button">Add initiative</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard title="Overview">
                    <p class="text-sm text-ink">{{ pillar.description || 'No description.' }}</p>
                </SectionCard>

                <SectionCard title="Initiatives" flush>
                    <template v-if="canManageInitiatives" #actions>
                        <Link :href="createInitiativeHref">
                            <SecondaryButton type="button" size="sm">Add initiative</SecondaryButton>
                        </Link>
                    </template>
                    <ul v-if="pillar.initiatives?.length" class="divide-y divide-border">
                        <li v-for="initiative in pillar.initiatives" :key="initiative.id" class="px-4 py-4 sm:px-6">
                            <Link :href="route('initiatives.show', initiative.id)" class="app-link font-medium">
                                {{ initiative.code ? initiative.code + ' — ' : '' }}{{ initiative.name }}
                            </Link>
                            <div class="mt-1 flex flex-wrap gap-2 text-sm text-ink-muted">
                                <StatusBadge :value="initiative.status" />
                                <span>{{ initiative.delivery_projects?.length || 0 }} delivery projects</span>
                                <span>{{ initiative.kpis?.length || 0 }} KPI outcomes</span>
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else title="No initiatives under this pillar" description="Create an initiative to turn this theme into executable bets.">
                        <template v-if="canManageInitiatives" #actions>
                            <Link :href="createInitiativeHref">
                                <PrimaryButton type="button" size="sm">Add initiative</PrimaryButton>
                            </Link>
                        </template>
                    </EmptyState>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ImprovementWorkspace from '@/Components/ImprovementWorkspace.vue';
import { Head, Link } from '@inertiajs/vue3';
import { phaseLabel } from '@/Lib/improvementPhases';

const props = defineProps({
    profile: Object,
    canManage: Boolean,
    canApproveGate: Boolean,
    features: Array,
    kpis: Array,
    users: Array,
});

const hubHref = () => {
    const id = props.profile?.delivery_project_id || props.profile?.delivery_project?.id;
    return id ? route('delivery-projects.show', { deliveryProject: id, tab: 'improve' }) : route('dmaic.index');
};
</script>

<template>
    <Head :title="profile?.delivery_project?.name || 'Improvement cycle'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="profile?.delivery_project?.name || 'Improvement cycle'">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Improvement cycles', href: route('dmaic.index') },
                        { label: profile?.delivery_project?.name || 'Cycle' },
                    ]" />
                </template>
                <template #description>
                    <span v-if="profile">
                        {{ phaseLabel(profile.current_phase) }} · <StatusBadge :value="profile.status" />
                    </span>
                </template>
                <template #actions>
                    <Link v-if="profile?.delivery_project?.id" :href="hubHref()" class="app-link text-sm font-medium">
                        Open project hub (Improve tab) →
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard v-if="!profile" title="Redirecting">
                    <p class="text-sm text-ink-muted">
                        Improvement workspaces live on the delivery project hub. Use the
                        <Link :href="route('dmaic.index')" class="app-link">improvement cycles list</Link>
                        to open a project.
                    </p>
                </SectionCard>
                <ImprovementWorkspace
                    v-else
                    :profile="profile"
                    :can-manage="canManage"
                    :can-approve-gate="canApproveGate"
                    :features="features"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

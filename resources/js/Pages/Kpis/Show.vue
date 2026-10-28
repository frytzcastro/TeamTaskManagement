<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import StatCard from '@/Components/StatCard.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    kpi: Object,
    canManage: Boolean,
});

const measurementForm = useForm({
    value: '',
    measured_at: new Date().toISOString().slice(0, 16),
    notes: '',
    trend: '',
    status: '',
});

const addMeasurement = () => {
    measurementForm.post(route('kpis.measurements.store', props.kpi.id), {
        onSuccess: () => measurementForm.reset('value', 'notes', 'trend', 'status'),
    });
};
</script>

<template>
    <Head :title="kpi.name" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="kpi.name">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'KPI Outcomes', href: route('kpis.index') },
                        { label: kpi.name },
                    ]" />
                </template>
                <template #actions>
                    <Link v-if="canManage" :href="route('kpis.edit', kpi.id)">
                        <PrimaryButton type="button">Edit</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <SectionCard class="lg:col-span-2" title="Definition">
                        <p class="text-sm text-ink">{{ kpi.definition || 'No definition.' }}</p>
                        <p class="mt-3 text-sm text-ink-muted"><span class="font-medium text-ink">Formula:</span> {{ kpi.formula || '—' }}</p>
                        <div class="mt-4 grid grid-cols-3 gap-4">
                            <StatCard label="Baseline" :value="kpi.baseline ?? '—'" />
                            <StatCard label="Target" :value="kpi.target ?? '—'" />
                            <StatCard label="Current" :value="kpi.current_value ?? '—'" />
                        </div>
                    </SectionCard>
                    <SectionCard title="Summary">
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between gap-2">
                                <dt class="text-ink-muted">Status</dt>
                                <dd><StatusBadge :value="kpi.status" /></dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-ink-muted">Trend</dt>
                                <dd class="text-ink">{{ kpi.trend || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-ink-muted">Direction</dt>
                                <dd class="text-ink capitalize">{{ kpi.direction?.replaceAll('_', ' ') }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-ink-muted">Owner</dt>
                                <dd class="text-ink">{{ kpi.owner?.name || '—' }}</dd>
                            </div>
                        </dl>
                    </SectionCard>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <SectionCard title="Linked initiatives" flush>
                        <ul v-if="kpi.initiatives?.length" class="divide-y divide-border">
                            <li v-for="i in kpi.initiatives" :key="i.id" class="px-4 py-3 sm:px-6">
                                <Link :href="route('initiatives.show', i.id)" class="app-link font-medium">{{ i.name }}</Link>
                            </li>
                        </ul>
                        <EmptyState v-else title="No initiatives linked" />
                    </SectionCard>
                    <SectionCard title="Linked delivery projects" flush>
                        <ul v-if="kpi.delivery_projects?.length" class="divide-y divide-border">
                            <li v-for="p in kpi.delivery_projects" :key="p.id" class="px-4 py-3 sm:px-6">
                                <Link :href="route('delivery-projects.show', p.id)" class="app-link font-medium">{{ p.name }}</Link>
                            </li>
                        </ul>
                        <EmptyState v-else title="No delivery projects linked" />
                    </SectionCard>
                </div>

                <SectionCard title="Measurements" flush>
                    <ul v-if="kpi.measurements?.length" class="divide-y divide-border">
                        <li v-for="m in kpi.measurements" :key="m.id" class="flex justify-between px-4 py-3 text-sm sm:px-6">
                            <span class="font-medium text-ink">{{ m.value }}</span>
                            <span class="text-ink-muted">{{ m.measured_at }}</span>
                        </li>
                    </ul>
                    <EmptyState v-else title="No measurements recorded" />
                    <form v-if="canManage" @submit.prevent="addMeasurement" class="grid gap-3 border-t border-border p-4 sm:grid-cols-4 sm:px-6">
                        <div>
                            <InputLabel value="Value" />
                            <TextInput v-model="measurementForm.value" type="number" step="0.0001" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Measured at" />
                            <TextInput v-model="measurementForm.measured_at" type="datetime-local" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Trend" />
                            <TextInput v-model="measurementForm.trend" class="mt-1 block w-full" placeholder="improving" />
                        </div>
                        <div class="flex items-end">
                            <PrimaryButton :disabled="measurementForm.processing">Record</PrimaryButton>
                        </div>
                    </form>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

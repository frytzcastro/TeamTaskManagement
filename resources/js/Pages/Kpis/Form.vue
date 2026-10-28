<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    kpi: Object,
    users: Array,
    initiatives: Array,
    deliveryProjects: Array,
});

const isEdit = computed(() => !!props.kpi);

const form = useForm({
    metric_layer: props.kpi?.metric_layer || 'business',
    name: props.kpi?.name || '',
    definition: props.kpi?.definition || '',
    formula: props.kpi?.formula || '',
    owner_id: props.kpi?.owner_id || '',
    data_source: props.kpi?.data_source || '',
    baseline: props.kpi?.baseline ?? '',
    target: props.kpi?.target ?? '',
    current_value: props.kpi?.current_value ?? '',
    reporting_period: props.kpi?.reporting_period || '',
    direction: props.kpi?.direction || 'lower_is_better',
    status: props.kpi?.status || 'no_data',
    trend: props.kpi?.trend || '',
    unit: props.kpi?.unit || '%',
    initiative_ids: props.kpi?.initiatives?.map((i) => i.id) || [],
    delivery_project_ids: props.kpi?.delivery_projects?.map((p) => p.id) || [],
});

const submit = () => {
    if (isEdit.value) form.put(route('kpis.update', props.kpi.id));
    else form.post(route('kpis.store'));
};
</script>

<template>
    <Head :title="isEdit ? 'Edit KPI' : 'New KPI'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit KPI' : 'New KPI'">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'KPI Outcomes', href: route('kpis.index') },
                        { label: isEdit ? 'Edit' : 'New' },
                    ]" />
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <SectionCard>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel value="Name" />
                            <TextInput v-model="form.name" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="form.errors.name" />
                            <p v-if="isEdit && kpi?.code" class="mt-1 text-xs text-ink-muted">Code {{ kpi.code }} (auto-assigned)</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Metric layer" />
                                <SelectInput v-model="form.metric_layer" class="mt-1">
                                    <option value="business">Business</option>
                                    <option value="process">Process</option>
                                    <option value="six_sigma">Six Sigma</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Unit" />
                                <TextInput v-model="form.unit" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="Baseline" />
                                <TextInput v-model="form.baseline" type="number" step="0.0001" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="Target" />
                                <TextInput v-model="form.target" type="number" step="0.0001" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="Current" />
                                <TextInput v-model="form.current_value" type="number" step="0.0001" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="Direction" />
                                <SelectInput v-model="form.direction" class="mt-1">
                                    <option value="lower_is_better">Lower is better</option>
                                    <option value="higher_is_better">Higher is better</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Owner" />
                                <SelectInput v-model="form.owner_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </SelectInput>
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Definition" />
                            <TextareaInput v-model="form.definition" :rows="2" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Formula" />
                            <TextareaInput v-model="form.formula" :rows="2" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Initiatives" />
                            <select v-model="form.initiative_ids" multiple size="3" class="mt-1 block w-full rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand">
                                <option v-for="i in initiatives" :key="i.id" :value="i.id">{{ i.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Delivery projects" />
                            <select v-model="form.delivery_project_ids" multiple size="3" class="mt-1 block w-full rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand">
                                <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                            <Link :href="isEdit ? route('kpis.show', kpi.id) : route('kpis.index')">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

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
    deliveryProject: Object,
    initiatives: Array,
    users: Array,
    teams: Array,
    kpis: Array,
});

const isEdit = computed(() => !!props.deliveryProject);

const form = useForm({
    type: props.deliveryProject?.type || 'standard',
    initiative_id: props.deliveryProject?.initiative_id || props.initiatives[0]?.id || '',
    name: props.deliveryProject?.name || '',
    project_owner_id: props.deliveryProject?.project_owner_id || '',
    product_owner_id: props.deliveryProject?.product_owner_id || '',
    team_id: props.deliveryProject?.team_id || '',
    start_date: props.deliveryProject?.start_date?.substring?.(0, 10) || props.deliveryProject?.start_date || '',
    target_completion: props.deliveryProject?.target_completion?.substring?.(0, 10) || props.deliveryProject?.target_completion || '',
    status: props.deliveryProject?.status || 'planned',
    priority: props.deliveryProject?.priority || 'medium',
    description: props.deliveryProject?.description || '',
    success_criteria: props.deliveryProject?.success_criteria || '',
    risks: props.deliveryProject?.risks || '',
    dependencies: props.deliveryProject?.dependencies || '',
    kpi_ids: props.deliveryProject?.kpis?.map((k) => k.id) || [],
    member_ids: props.deliveryProject?.members?.map((m) => m.id) || [],
});

const submit = () => {
    if (isEdit.value) form.put(route('delivery-projects.update', props.deliveryProject.id));
    else form.post(route('delivery-projects.store'));
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Delivery Project' : 'New Delivery Project'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit Delivery Project' : 'New Delivery Project'">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Projects', href: route('delivery-projects.index') },
                        { label: isEdit ? 'Edit' : 'New' },
                    ]" />
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <SectionCard>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <InputLabel value="Name" />
                                <TextInput v-model="form.name" class="mt-1 block w-full" required />
                                <InputError class="mt-1" :message="form.errors.name" />
                            </div>
                            <div>
                                <InputLabel value="Initiative" />
                                <SelectInput v-model="form.initiative_id" class="mt-1" required>
                                    <option v-for="i in initiatives" :key="i.id" :value="i.id">{{ i.name }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.initiative_id" />
                            </div>
                            <div v-if="isEdit && deliveryProject?.code">
                                <InputLabel value="Code" />
                                <p class="mt-1 text-sm text-ink">{{ deliveryProject.code }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">Assigned automatically</p>
                            </div>
                            <div>
                                <InputLabel value="Project type" />
                                <SelectInput v-model="form.type" class="mt-1">
                                    <option value="standard">Standard delivery</option>
                                    <option value="dmaic">Structured improvement</option>
                                </SelectInput>
                                <p class="mt-1 text-xs text-ink-muted">
                                    Adds a Frame → Baseline → Root causes → Fixes → Sustain workspace for outcome-driven work; backlog and tasks still use this delivery project.
                                    <span class="text-slate-400">Uses the DMAIC method.</span>
                                </p>
                                <InputError class="mt-1" :message="form.errors.type" />
                            </div>
                            <div>
                                <InputLabel value="Delivery Owner" />
                                <SelectInput v-model="form.project_owner_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.project_owner_id" />
                            </div>
                            <div>
                                <InputLabel value="Product Owner" />
                                <SelectInput v-model="form.product_owner_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.product_owner_id" />
                            </div>
                            <div>
                                <InputLabel value="Target Completion" />
                                <TextInput v-model="form.target_completion" type="date" class="mt-1 block w-full" />
                                <InputError class="mt-1" :message="form.errors.target_completion" />
                            </div>
                            <div>
                                <InputLabel value="Status" />
                                <SelectInput v-model="form.status" class="mt-1">
                                    <option value="planned">Planned</option>
                                    <option value="active">Active</option>
                                    <option value="at_risk">At Risk</option>
                                    <option value="completed">Completed</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.status" />
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Description" />
                            <TextareaInput v-model="form.description" :rows="3" class="mt-1" />
                            <InputError class="mt-1" :message="form.errors.description" />
                        </div>
                        <div>
                            <InputLabel value="Success Criteria" />
                            <TextareaInput v-model="form.success_criteria" :rows="2" class="mt-1" />
                            <InputError class="mt-1" :message="form.errors.success_criteria" />
                        </div>
                        <div>
                            <InputLabel value="Members" />
                            <select v-model="form.member_ids" multiple size="4" class="mt-1 block w-full rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand">
                                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.member_ids" />
                        </div>
                        <div>
                            <InputLabel value="KPI Outcomes" />
                            <select v-model="form.kpi_ids" multiple size="3" class="mt-1 block w-full rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand">
                                <option v-for="k in kpis" :key="k.id" :value="k.id">{{ k.name }}</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.kpi_ids" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                            <Link :href="isEdit ? route('delivery-projects.show', deliveryProject.id) : route('delivery-projects.index')">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

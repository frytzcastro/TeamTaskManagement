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
    initiative: Object,
    pillars: Array,
    users: Array,
    kpis: Array,
    systems: Array,
    defaultStrategicPillarId: [String, Number],
    returnPillar: Object,
});

const isEdit = computed(() => !!props.initiative);
const fromPillar = computed(() => !isEdit.value && !!props.returnPillar);

const form = useForm({
    strategic_pillar_id: props.initiative?.strategic_pillar_id
        || props.defaultStrategicPillarId
        || props.pillars[0]?.id
        || '',
    name: props.initiative?.name || '',
    owner_id: props.initiative?.owner_id || '',
    start_date: props.initiative?.start_date?.substring?.(0, 10) || props.initiative?.start_date || '',
    target_date: props.initiative?.target_date?.substring?.(0, 10) || props.initiative?.target_date || '',
    priority: props.initiative?.priority || 'medium',
    status: props.initiative?.status || 'planned',
    business_goal: props.initiative?.business_goal || '',
    problem: props.initiative?.problem || '',
    opportunity: props.initiative?.opportunity || '',
    expected_business_impact: props.initiative?.expected_business_impact || '',
    success_criteria: props.initiative?.success_criteria || '',
    dependencies: props.initiative?.dependencies || '',
    risks: props.initiative?.risks || '',
    kpi_ids: props.initiative?.kpis?.map((k) => k.id) || [],
    system_ids: props.initiative?.systems?.map((s) => s.id) || [],
    return_pillar_id: props.returnPillar?.id || '',
});

const crumbs = computed(() => {
    if (fromPillar.value) {
        return [
            { label: 'Strategic pillars', href: route('pillars.index') },
            { label: props.returnPillar.name, href: route('pillars.show', props.returnPillar.id) },
            { label: 'New initiative' },
        ];
    }
    return [
        { label: 'Initiatives', href: route('initiatives.index') },
        { label: isEdit.value ? 'Edit' : 'New' },
    ];
});

const cancelHref = computed(() => {
    if (isEdit.value) return route('initiatives.show', props.initiative.id);
    if (fromPillar.value) return route('pillars.show', props.returnPillar.id);
    return route('initiatives.index');
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('initiatives.update', props.initiative.id));
    } else {
        form.post(route('initiatives.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Initiative' : 'New Initiative'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit Initiative' : (fromPillar ? `New initiative · ${returnPillar.name}` : 'New Initiative')">
                <template #breadcrumbs>
                    <Breadcrumbs :items="crumbs" />
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
                                <p v-if="isEdit && initiative?.code" class="mt-1 text-xs text-ink-muted">Code {{ initiative.code }} (auto-assigned)</p>
                            </div>
                            <div>
                                <InputLabel value="Strategic pillar" />
                                <SelectInput v-model="form.strategic_pillar_id" class="mt-1" required :disabled="fromPillar">
                                    <option v-for="p in pillars" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </SelectInput>
                                <p v-if="fromPillar" class="mt-1 text-xs text-ink-muted">Pillar is set from the parent page.</p>
                            </div>
                            <div>
                                <InputLabel value="Owner" />
                                <SelectInput v-model="form.owner_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Priority" />
                                <SelectInput v-model="form.priority" class="mt-1">
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Status" />
                                <SelectInput v-model="form.status" class="mt-1">
                                    <option value="planned">Planned</option>
                                    <option value="active">Active</option>
                                    <option value="at_risk">At Risk</option>
                                    <option value="completed">Completed</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Start date" />
                                <TextInput v-model="form.start_date" type="date" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="Target date" />
                                <TextInput v-model="form.target_date" type="date" class="mt-1 block w-full" />
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Business goal" />
                            <TextareaInput v-model="form.business_goal" :rows="2" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Problem" />
                            <TextareaInput v-model="form.problem" :rows="2" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Success criteria" />
                            <TextareaInput v-model="form.success_criteria" :rows="2" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Systems touched" />
                            <select v-model="form.system_ids" multiple size="4" class="mt-1 block w-full rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand">
                                <option v-for="s in systems" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p class="mt-1 text-xs text-ink-muted">Systems this initiative will change. Features are assigned into projects from these systems.</p>
                            <InputError class="mt-1" :message="form.errors.system_ids" />
                        </div>
                        <div>
                            <InputLabel value="Linked KPIs" />
                            <select v-model="form.kpi_ids" multiple size="4" class="mt-1 block w-full rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand">
                                <option v-for="k in kpis" :key="k.id" :value="k.id">{{ k.name }}</option>
                            </select>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                            <Link :href="cancelHref">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

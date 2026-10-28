<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import SectionCard from '@/Components/SectionCard.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    feature: Object,
    deliveryProjects: Array,
    epics: Array,
    systems: Array,
    defaultSystemId: [String, Number],
    defaultDeliveryProjectId: [String, Number],
    defaultEpicId: [String, Number],
    defaultDodChecklist: Array,
    returnProject: Object,
    returnTab: String,
});
const isEdit = computed(() => !!props.feature);
const fromHub = computed(() => !isEdit.value && !!props.returnProject);
const form = useForm({
    title: props.feature?.title || '',
    system_id: props.feature?.system_id || props.defaultSystemId || props.systems[0]?.id || '',
    delivery_project_id: props.feature?.delivery_project_id || props.defaultDeliveryProjectId || props.returnProject?.id || '',
    epic_id: props.feature?.epic_id || props.defaultEpicId || '',
    description: props.feature?.description || '',
    acceptance_criteria: props.feature?.acceptance_criteria || '',
    dod_checklist: props.feature?.dod_checklist?.length
        ? props.feature.dod_checklist
        : (props.defaultDodChecklist || []),
    priority: props.feature?.priority || 'medium',
    status: props.feature?.status || 'planned',
    start_date: props.feature?.start_date?.substring?.(0, 10) || props.feature?.start_date || '',
    due_date: props.feature?.due_date?.substring?.(0, 10) || props.feature?.due_date || '',
    return_tab: props.returnTab || '',
    return_project_id: props.returnProject?.id || '',
});
const epicsForSystem = computed(() =>
    (props.epics || []).filter((e) => !form.system_id || e.system_id === form.system_id),
);
const crumbs = computed(() => {
    if (fromHub.value) {
        return [
            { label: 'Projects', href: route('delivery-projects.index') },
            { label: props.returnProject.name, href: route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'plan' }) },
            { label: 'New Feature' },
        ];
    }
    if (props.defaultSystemId && !isEdit.value) {
        const sys = props.systems.find((s) => s.id === props.defaultSystemId);
        return [
            { label: 'Systems', href: route('systems.index') },
            { label: sys?.name || 'System', href: route('systems.show', props.defaultSystemId) },
            { label: 'New Feature' },
        ];
    }
    return [
        { label: 'Features', href: route('features.index') },
        { label: isEdit.value ? 'Edit' : 'New' },
    ];
});
const cancelHref = computed(() => {
    if (fromHub.value) {
        return route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'plan' });
    }
    if (isEdit.value) return route('features.show', props.feature.id);
    if (props.defaultSystemId) return route('systems.show', props.defaultSystemId);
    return route('features.index');
});
const submit = () => (isEdit.value ? form.put(route('features.update', props.feature.id)) : form.post(route('features.store')));
</script>

<template>
    <Head :title="isEdit ? 'Edit Feature' : 'New Feature'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit Feature' : 'New Feature'">
                <template #breadcrumbs>
                    <Breadcrumbs :items="crumbs" />
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <SectionCard>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel value="Title" />
                            <TextInput v-model="form.title" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="System" />
                            <SelectInput v-model="form.system_id" class="mt-1" required :disabled="!!defaultSystemId && !isEdit">
                                <option v-for="s in systems" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel value="Epic" />
                            <SelectInput v-model="form.epic_id" class="mt-1">
                                <option value="">—</option>
                                <option v-for="e in epicsForSystem" :key="e.id" :value="e.id">{{ e.title }}</option>
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel value="Delivery project (optional — leave empty for backlog)" />
                            <SelectInput v-model="form.delivery_project_id" class="mt-1" :disabled="fromHub">
                                <option value="">Backlog (unassigned)</option>
                                <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel value="Acceptance criteria" />
                            <TextareaInput v-model="form.acceptance_criteria" :rows="3" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Definition of Done" />
                            <ul class="mt-2 space-y-2 rounded-md border border-border divide-y divide-border">
                                <li v-for="(item, i) in form.dod_checklist" :key="item.key" class="flex items-center gap-3 px-4 py-3 text-sm">
                                    <Checkbox v-model:checked="form.dod_checklist[i].completed" :name="`dod_${item.key}`" />
                                    <span class="text-ink">{{ item.label }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Planned start" />
                                <TextInput v-model="form.start_date" type="date" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="Planned due" />
                                <TextInput v-model="form.due_date" type="date" class="mt-1 block w-full" />
                            </div>
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

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
    bug: Object,
    deliveryProjects: Array,
    features: Array,
    systems: Array,
    tasks: Array,
    users: Array,
    dmaicProfiles: Array,
    rootCauses: Array,
    improvements: Array,
    statuses: Array,
    severities: Array,
    environments: Array,
    defaults: Object,
    returnProject: Object,
    returnTab: String,
});

const isEdit = computed(() => !!props.bug);
const fromHub = computed(() => !isEdit.value && !!props.returnProject);

const form = useForm({
    title: props.bug?.title || '',
    description: props.bug?.description || '',
    steps_to_reproduce: props.bug?.steps_to_reproduce || '',
    expected_result: props.bug?.expected_result || '',
    actual_result: props.bug?.actual_result || '',
    environment: props.bug?.environment || 'development',
    severity: props.bug?.severity || 'medium',
    status: props.bug?.status || 'reported',
    assignee_id: props.bug?.assignee_id || '',
    delivery_project_id: props.bug?.delivery_project_id || props.defaults?.delivery_project_id || '',
    feature_id: props.bug?.feature_id || props.defaults?.feature_id || '',
    system_id: props.bug?.system_id || props.defaults?.system_id || '',
    task_id: props.bug?.task_id || props.defaults?.task_id || '',
    dmaic_profile_id: props.bug?.dmaic_profile_id || '',
    dmaic_root_cause_id: props.bug?.dmaic_root_cause_id || '',
    dmaic_improvement_id: props.bug?.dmaic_improvement_id || '',
    return_tab: props.returnTab || '',
    return_project_id: props.returnProject?.id || '',
});

const crumbs = computed(() => {
    if (fromHub.value) {
        return [
            { label: 'Projects', href: route('delivery-projects.index') },
            { label: props.returnProject.name, href: route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'quality' }) },
            { label: 'Report Bug' },
        ];
    }
    return [
        { label: 'Bugs', href: route('bugs.index') },
        { label: isEdit.value ? 'Edit' : 'Report' },
    ];
});
const cancelHref = computed(() => {
    if (fromHub.value) {
        return route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'quality' });
    }
    return isEdit.value ? route('bugs.show', props.bug.id) : route('bugs.index');
});

const submit = () => {
    if (isEdit.value) form.put(route('bugs.update', props.bug.id));
    else form.post(route('bugs.store'));
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Bug' : 'Report Bug'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit Bug' : 'Report Bug'">
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
                            <InputError class="mt-1" :message="form.errors.title" />
                        </div>
                        <div>
                            <InputLabel value="Description" />
                            <TextareaInput v-model="form.description" :rows="3" class="mt-1" />
                            <InputError class="mt-1" :message="form.errors.description" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Severity" />
                                <SelectInput v-model="form.severity" class="mt-1">
                                    <option v-for="s in severities" :key="s" :value="s">{{ s }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.severity" />
                            </div>
                            <div>
                                <InputLabel value="Environment" />
                                <SelectInput v-model="form.environment" class="mt-1">
                                    <option v-for="e in environments" :key="e" :value="e">{{ e }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.environment" />
                            </div>
                            <div>
                                <InputLabel value="Status" />
                                <SelectInput v-model="form.status" class="mt-1">
                                    <option v-for="s in statuses" :key="s" :value="s">{{ s.replaceAll('_', ' ') }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.status" />
                            </div>
                            <div>
                                <InputLabel value="Assignee" />
                                <SelectInput v-model="form.assignee_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.assignee_id" />
                            </div>
                            <div>
                                <InputLabel value="Delivery Project" />
                                <SelectInput v-model="form.delivery_project_id" class="mt-1" :disabled="fromHub">
                                    <option value="">—</option>
                                    <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.delivery_project_id" />
                            </div>
                            <div>
                                <InputLabel value="System" />
                                <SelectInput v-model="form.system_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="p in systems" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.system_id" />
                            </div>
                            <div>
                                <InputLabel value="Feature" />
                                <SelectInput v-model="form.feature_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="f in features" :key="f.id" :value="f.id">{{ f.title }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.feature_id" />
                            </div>
                            <div>
                                <InputLabel value="Related Task" />
                                <SelectInput v-model="form.task_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="t in tasks" :key="t.id" :value="t.id">{{ t.title }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.task_id" />
                            </div>
                            <div>
                                <InputLabel value="Improvement cycle" />
                                <SelectInput v-model="form.dmaic_profile_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="p in dmaicProfiles || []" :key="p.id" :value="p.id">
                                        {{ p.delivery_project?.name || ('Cycle #' + p.id) }} ({{ p.current_phase }})
                                    </option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.dmaic_profile_id" />
                            </div>
                            <div>
                                <InputLabel value="Root cause" />
                                <SelectInput v-model="form.dmaic_root_cause_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="c in rootCauses || []" :key="c.id" :value="c.id">{{ c.title }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.dmaic_root_cause_id" />
                            </div>
                            <div>
                                <InputLabel value="Improvement" />
                                <SelectInput v-model="form.dmaic_improvement_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="i in improvements || []" :key="i.id" :value="i.id">{{ i.title }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.dmaic_improvement_id" />
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Steps to reproduce" />
                            <TextareaInput v-model="form.steps_to_reproduce" :rows="3" class="mt-1" />
                            <InputError class="mt-1" :message="form.errors.steps_to_reproduce" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Expected result" />
                                <TextareaInput v-model="form.expected_result" :rows="2" class="mt-1" />
                                <InputError class="mt-1" :message="form.errors.expected_result" />
                            </div>
                            <div>
                                <InputLabel value="Actual result" />
                                <TextareaInput v-model="form.actual_result" :rows="2" class="mt-1" />
                                <InputError class="mt-1" :message="form.errors.actual_result" />
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

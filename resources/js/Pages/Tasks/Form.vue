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
import { computed, watch } from 'vue';

const props = defineProps({
    task: Object,
    deliveryProjects: Array,
    features: Array,
    sprints: Array,
    users: Array,
    tasks: Array,
    statuses: Array,
    defaultDeliveryProjectId: [String, Number],
    defaultFeatureId: [String, Number],
    returnProject: Object,
    returnTab: String,
    returnFeature: Object,
});

const isEdit = computed(() => !!props.task);
const fromHub = computed(() => !isEdit.value && !!props.returnProject && !props.returnFeature);
const fromFeature = computed(() => !isEdit.value && !!props.returnFeature);

const form = useForm({
    delivery_project_id: props.task?.delivery_project_id || props.defaultDeliveryProjectId || props.deliveryProjects[0]?.id || '',
    feature_id: props.task?.feature_id || props.defaultFeatureId || '',
    sprint_id: props.task?.sprint_id || '',
    parent_id: props.task?.parent_id || '',
    title: props.task?.title || '',
    description: props.task?.description || '',
    assignee_id: props.task?.assignee_id || '',
    reviewer_id: props.task?.reviewer_id || '',
    qa_id: props.task?.qa_id || '',
    priority: props.task?.priority || 'medium',
    status: props.task?.status || 'backlog',
    start_date: props.task?.start_date?.substring?.(0, 10) || props.task?.start_date || '',
    due_date: props.task?.due_date?.substring?.(0, 10) || props.task?.due_date || '',
    estimate: props.task?.estimate || '',
    actual_effort: props.task?.actual_effort || '',
    completion_percent: props.task?.completion_percent ?? 0,
    blockers: props.task?.blockers || '',
    git_reference: props.task?.git_reference || '',
    dependency_ids: props.task?.dependencies?.map((d) => d.id) || [],
    return_tab: props.returnTab || '',
    return_project_id: props.returnProject?.id || '',
    return_feature_id: props.returnFeature?.id || '',
});

const featuresForProject = computed(() =>
    (props.features || []).filter((f) => String(f.delivery_project_id) === String(form.delivery_project_id)),
);

const sprintsForProject = computed(() =>
    (props.sprints || []).filter((s) => String(s.delivery_project_id) === String(form.delivery_project_id)),
);

watch(() => form.delivery_project_id, () => {
    if (isEdit.value || fromFeature.value) return;
    const stillValid = featuresForProject.value.some((f) => String(f.id) === String(form.feature_id));
    if (!stillValid) form.feature_id = '';
    const sprintOk = sprintsForProject.value.some((s) => String(s.id) === String(form.sprint_id));
    if (!sprintOk) form.sprint_id = '';
});

const crumbs = computed(() => {
    if (fromFeature.value) {
        const items = [];
        if (props.returnProject) {
            items.push({ label: 'Projects', href: route('delivery-projects.index') });
            items.push({
                label: props.returnProject.name,
                href: route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: 'plan' }),
            });
        } else {
            items.push({ label: 'Features', href: route('features.index') });
        }
        items.push({ label: props.returnFeature.title, href: route('features.show', props.returnFeature.id) });
        items.push({ label: 'New Task' });
        return items;
    }
    if (fromHub.value) {
        return [
            { label: 'Projects', href: route('delivery-projects.index') },
            { label: props.returnProject.name, href: route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'build' }) },
            { label: 'New Task' },
        ];
    }
    return [
        { label: 'Tasks', href: route('tasks.index') },
        { label: isEdit.value ? 'Edit' : 'New' },
    ];
});

const cancelHref = computed(() => {
    if (fromFeature.value) {
        return route('features.show', props.returnFeature.id);
    }
    if (fromHub.value) {
        return route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'build' });
    }
    return isEdit.value ? route('tasks.show', props.task.id) : route('tasks.index');
});

const submit = () => {
    if (isEdit.value) form.put(route('tasks.update', props.task.id));
    else form.post(route('tasks.store'));
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Task' : 'New Task'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit Task' : 'New Task'">
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
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Delivery Project" />
                                <SelectInput v-model="form.delivery_project_id" class="mt-1" required :disabled="fromHub || fromFeature">
                                    <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.delivery_project_id" />
                            </div>
                            <div>
                                <InputLabel value="Feature" />
                                <SelectInput v-model="form.feature_id" class="mt-1" :disabled="fromFeature">
                                    <option value="">— Optional —</option>
                                    <option v-for="f in featuresForProject" :key="f.id" :value="f.id">{{ f.title }}</option>
                                </SelectInput>
                                <p class="mt-1 text-xs text-ink-muted">Prefer linking to a Plan feature so Build work stays traceable.</p>
                                <InputError class="mt-1" :message="form.errors.feature_id" />
                            </div>
                            <div>
                                <InputLabel value="Sprint" />
                                <SelectInput v-model="form.sprint_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="s in sprintsForProject" :key="s.id" :value="s.id">{{ s.name }} ({{ s.status }})</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.sprint_id" />
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
                                <InputLabel value="Priority" />
                                <SelectInput v-model="form.priority" class="mt-1">
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="form.errors.priority" />
                            </div>
                            <div>
                                <InputLabel value="Start date (commitment)" />
                                <TextInput v-model="form.start_date" type="date" class="mt-1 block w-full" />
                                <InputError class="mt-1" :message="form.errors.start_date" />
                            </div>
                            <div>
                                <InputLabel value="Due date (commitment)" />
                                <TextInput v-model="form.due_date" type="date" class="mt-1 block w-full" />
                                <InputError class="mt-1" :message="form.errors.due_date" />
                            </div>
                            <div>
                                <InputLabel value="Commitment estimate (working days)" />
                                <TextInput v-model="form.estimate" type="number" step="0.5" min="0" class="mt-1 block w-full" />
                                <p class="mt-1 text-xs text-ink-muted">Required before moving to in progress. Used for commitment score.</p>
                                <InputError class="mt-1" :message="form.errors.estimate" />
                            </div>
                            <div>
                                <InputLabel value="Actual effort (working days)" />
                                <TextInput v-model="form.actual_effort" type="number" step="0.5" min="0" class="mt-1 block w-full" />
                                <InputError class="mt-1" :message="form.errors.actual_effort" />
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Description" />
                            <TextareaInput v-model="form.description" :rows="3" class="mt-1" />
                            <InputError class="mt-1" :message="form.errors.description" />
                        </div>
                        <div>
                            <InputLabel value="Dependencies" />
                            <select v-model="form.dependency_ids" multiple size="4" class="mt-1 block w-full rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand">
                                <option v-for="t in tasks" :key="t.id" :value="t.id">{{ t.title }}</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.dependency_ids" />
                        </div>
                        <div>
                            <InputLabel value="Blockers" />
                            <TextareaInput v-model="form.blockers" :rows="2" class="mt-1" />
                            <InputError class="mt-1" :message="form.errors.blockers" />
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

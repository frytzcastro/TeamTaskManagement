<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import SectionCard from '@/Components/SectionCard.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    sprint: Object,
    deliveryProjects: Array,
    statuses: Array,
    defaultDeliveryProjectId: [String, Number],
    returnProject: Object,
    returnTab: String,
});
const isEdit = computed(() => !!props.sprint);
const fromHub = computed(() => !isEdit.value && !!props.returnProject);
const form = useForm({
    delivery_project_id: props.sprint?.delivery_project_id || props.defaultDeliveryProjectId || '',
    name: props.sprint?.name || '',
    start_date: props.sprint?.start_date || '',
    end_date: props.sprint?.end_date || '',
    status: props.sprint?.status || 'planned',
    goal: props.sprint?.goal || '',
    return_tab: props.returnTab || '',
    return_project_id: props.returnProject?.id || '',
});
const crumbs = computed(() => {
    if (fromHub.value) {
        return [
            { label: 'Projects', href: route('delivery-projects.index') },
            { label: props.returnProject.name, href: route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'build' }) },
            { label: 'New Sprint' },
        ];
    }
    return [
        { label: 'Sprints', href: route('sprints.index') },
        { label: isEdit.value ? 'Edit' : 'New' },
    ];
});
const cancelHref = computed(() => {
    if (fromHub.value) {
        return route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'build' });
    }
    return isEdit.value ? route('sprints.show', props.sprint.id) : route('sprints.index');
});
const submit = () => isEdit.value ? form.put(route('sprints.update', props.sprint.id)) : form.post(route('sprints.store'));
</script>

<template>
    <Head :title="isEdit ? 'Edit Sprint' : 'New Sprint'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit Sprint' : 'New Sprint'">
                <template #breadcrumbs>
                    <Breadcrumbs :items="crumbs" />
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <SectionCard>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div>
                            <InputLabel value="Name" />
                            <TextInput v-model="form.name" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Delivery project" />
                            <SelectInput v-model="form.delivery_project_id" class="mt-1" required :disabled="fromHub">
                                <option value="">—</option>
                                <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </SelectInput>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <InputLabel value="Start" />
                                <TextInput v-model="form.start_date" type="date" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="End" />
                                <TextInput v-model="form.end_date" type="date" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="Status" />
                                <SelectInput v-model="form.status" class="mt-1">
                                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                                </SelectInput>
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Goal" />
                            <TextareaInput v-model="form.goal" :rows="2" class="mt-1" />
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

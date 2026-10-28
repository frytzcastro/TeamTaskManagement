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
    release: Object,
    deliveryProjects: Array,
    systems: Array,
    statuses: Array,
    environments: Array,
    defaultDeliveryProjectId: [String, Number],
    returnProject: Object,
    returnTab: String,
});
const isEdit = computed(() => !!props.release);
const fromHub = computed(() => !isEdit.value && !!props.returnProject);
const form = useForm({
    delivery_project_id: props.release?.delivery_project_id || props.defaultDeliveryProjectId || '',
    system_id: props.release?.system_id || '',
    name: props.release?.name || '',
    version: props.release?.version || '',
    environment: props.release?.environment || 'staging',
    status: props.release?.status || 'planned',
    target_date: props.release?.target_date || '',
    notes: props.release?.notes || '',
    return_tab: props.returnTab || '',
    return_project_id: props.returnProject?.id || '',
});
const crumbs = computed(() => {
    if (fromHub.value) {
        return [
            { label: 'Projects', href: route('delivery-projects.index') },
            { label: props.returnProject.name, href: route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'ship' }) },
            { label: 'New Release' },
        ];
    }
    return [
        { label: 'Releases', href: route('releases.index') },
        { label: isEdit.value ? 'Edit' : 'New' },
    ];
});
const cancelHref = computed(() => {
    if (fromHub.value) {
        return route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'ship' });
    }
    return isEdit.value ? route('releases.show', props.release.id) : route('releases.index');
});
const submit = () => isEdit.value ? form.put(route('releases.update', props.release.id)) : form.post(route('releases.store'));
</script>

<template>
    <Head :title="isEdit ? 'Edit Release' : 'New Release'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit Release' : 'New Release'">
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
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Version" />
                                <TextInput v-model="form.version" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="Target date" />
                                <TextInput v-model="form.target_date" type="date" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="Delivery project" />
                                <SelectInput v-model="form.delivery_project_id" class="mt-1" required :disabled="fromHub">
                                    <option value="">—</option>
                                    <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="System" />
                                <SelectInput v-model="form.system_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="p in systems" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Environment" />
                                <SelectInput v-model="form.environment" class="mt-1">
                                    <option v-for="e in environments" :key="e" :value="e">{{ e }}</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Status" />
                                <SelectInput v-model="form.status" class="mt-1">
                                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                                </SelectInput>
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Notes" />
                            <TextareaInput v-model="form.notes" :rows="3" class="mt-1" />
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

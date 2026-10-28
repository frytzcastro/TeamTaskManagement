<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    problem: Object,
    initiatives: Array,
    users: Array,
    defaultInitiativeId: [String, Number],
    returnProject: Object,
    returnTab: String,
});
const isEdit = computed(() => !!props.problem);
const fromHub = computed(() => !isEdit.value && !!props.returnProject);
const form = useForm({
    title: props.problem?.title || '',
    initiative_id: props.problem?.initiative_id || props.defaultInitiativeId || props.initiatives[0]?.id || '',
    problem_statement: props.problem?.problem_statement || '',
    business_impact: props.problem?.business_impact || '',
    customer_impact: props.problem?.customer_impact || '',
    opportunity: props.problem?.opportunity || '',
    status: props.problem?.status || 'open',
    owner_id: props.problem?.owner_id || '',
    return_tab: props.returnTab || '',
    return_project_id: props.returnProject?.id || '',
});
const crumbs = computed(() => {
    if (fromHub.value) {
        return [
            { label: 'Projects', href: route('delivery-projects.index') },
            { label: props.returnProject.name, href: route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'improve' }) },
            { label: 'New Problem' },
        ];
    }
    return [
        { label: 'Business problems', href: route('business-problems.index') },
        { label: isEdit.value ? 'Edit' : 'New' },
    ];
});
const cancelHref = computed(() => {
    if (fromHub.value) {
        return route('delivery-projects.show', { deliveryProject: props.returnProject.id, tab: props.returnTab || 'improve' });
    }
    return isEdit.value ? route('business-problems.show', props.problem.id) : route('business-problems.index');
});
const submit = () => (isEdit.value ? form.put(route('business-problems.update', props.problem.id)) : form.post(route('business-problems.store')));
</script>

<template>
    <Head :title="isEdit ? 'Edit Problem' : 'New Problem'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit business problem' : 'New business problem'">
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
                            <InputLabel value="Initiative" />
                            <SelectInput v-model="form.initiative_id" class="mt-1" required :disabled="fromHub && !!defaultInitiativeId">
                                <option v-for="i in initiatives" :key="i.id" :value="i.id">{{ i.name }}</option>
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel value="Problem statement" />
                            <TextareaInput v-model="form.problem_statement" :rows="3" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Business impact" />
                            <TextareaInput v-model="form.business_impact" :rows="2" class="mt-1" />
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

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
    ticket: Object,
    users: Array,
    systems: Array,
    deliveryProjects: Array,
    categories: Array,
    priorities: Array,
    statuses: Array,
});

const isEdit = computed(() => !!props.ticket);
const form = useForm({
    title: props.ticket?.title || '',
    description: props.ticket?.description || '',
    category: props.ticket?.category || 'other',
    priority: props.ticket?.priority || 'medium',
    status: props.ticket?.status || 'open',
    assignee_id: props.ticket?.assignee_id || '',
    system_id: props.ticket?.system_id || '',
    delivery_project_id: props.ticket?.delivery_project_id || '',
});

const submit = () => {
    if (isEdit.value) form.put(route('support.update', props.ticket.id));
    else form.post(route('support.store'));
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Ticket' : 'New Ticket'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit Ticket' : 'New Support Ticket'">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Support', href: route('support.index') },
                        { label: isEdit ? 'Edit' : 'New ticket' },
                    ]" />
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <SectionCard>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div>
                            <InputLabel value="Title" />
                            <TextInput v-model="form.title" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Description" />
                            <TextareaInput v-model="form.description" :rows="4" class="mt-1" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Category" />
                                <SelectInput v-model="form.category" class="mt-1">
                                    <option v-for="c in categories" :key="c" :value="c">{{ c.replaceAll('_', ' ') }}</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Priority" />
                                <SelectInput v-model="form.priority" class="mt-1">
                                    <option v-for="p in priorities" :key="p" :value="p">{{ p }}</option>
                                </SelectInput>
                            </div>
                            <div v-if="isEdit">
                                <InputLabel value="Status" />
                                <SelectInput v-model="form.status" class="mt-1">
                                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Assignee" />
                                <SelectInput v-model="form.assignee_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
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
                                <InputLabel value="Delivery project" />
                                <SelectInput v-model="form.delivery_project_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </SelectInput>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                            <Link :href="isEdit ? route('support.show', ticket.id) : route('support.index')">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

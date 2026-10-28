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
    epic: Object,
    systems: Array,
    defaultSystemId: [String, Number],
});
const isEdit = computed(() => !!props.epic);
const form = useForm({
    title: props.epic?.title || '',
    system_id: props.epic?.system_id || props.defaultSystemId || props.systems[0]?.id || '',
    description: props.epic?.description || '',
    priority: props.epic?.priority || 'medium',
    status: props.epic?.status || 'planned',
    start_date: props.epic?.start_date?.substring?.(0, 10) || props.epic?.start_date || '',
    due_date: props.epic?.due_date?.substring?.(0, 10) || props.epic?.due_date || '',
});
const crumbs = computed(() => {
    if (props.defaultSystemId && !isEdit.value) {
        const sys = props.systems.find((s) => s.id === props.defaultSystemId);
        return [
            { label: 'Systems', href: route('systems.index') },
            { label: sys?.name || 'System', href: route('systems.show', props.defaultSystemId) },
            { label: 'New Epic' },
        ];
    }
    return [
        { label: 'Epics', href: route('epics.index') },
        { label: isEdit.value ? 'Edit' : 'New' },
    ];
});
const cancelHref = computed(() => {
    if (isEdit.value) return route('epics.show', props.epic.id);
    if (props.defaultSystemId) return route('systems.show', props.defaultSystemId);
    return route('epics.index');
});
const submit = () => (isEdit.value ? form.put(route('epics.update', props.epic.id)) : form.post(route('epics.store')));
</script>

<template>
    <Head :title="isEdit ? 'Edit Epic' : 'New Epic'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit Epic' : 'New Epic'">
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
                            <InputLabel value="Description" />
                            <TextareaInput v-model="form.description" :rows="3" class="mt-1" />
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

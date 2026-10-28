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
    system: Object,
    users: Array,
});

const isEdit = computed(() => !!props.system);

const form = useForm({
    name: props.system?.name || '',
    type: props.system?.type || 'app',
    description: props.system?.description || '',
    owner_id: props.system?.owner_id || '',
    status: props.system?.status || 'active',
});

const submit = () => {
    if (isEdit.value) form.put(route('systems.update', props.system.id));
    else form.post(route('systems.store'));
};
</script>

<template>
    <Head :title="isEdit ? 'Edit System' : 'New System'" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEdit ? 'Edit system' : 'New system'">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Systems', href: route('systems.index') },
                        { label: isEdit ? 'Edit' : 'New' },
                    ]" />
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <SectionCard>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel value="Name" />
                            <TextInput v-model="form.name" class="mt-1 block w-full" required />
                            <InputError class="mt-1" :message="form.errors.name" />
                            <p v-if="isEdit && system?.code" class="mt-1 text-xs text-ink-muted">Code {{ system.code }} (auto-assigned)</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Type" />
                                <SelectInput v-model="form.type" class="mt-1">
                                    <option value="app">App</option>
                                    <option value="service">Service</option>
                                    <option value="platform">Platform</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Owner" />
                                <SelectInput v-model="form.owner_id" class="mt-1">
                                    <option value="">—</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Status" />
                                <SelectInput v-model="form.status" class="mt-1">
                                    <option value="active">Active</option>
                                    <option value="deprecated">Deprecated</option>
                                    <option value="planned">Planned</option>
                                </SelectInput>
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Description" />
                            <TextareaInput v-model="form.description" :rows="3" class="mt-1" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                            <Link :href="isEdit ? route('systems.show', system.id) : route('systems.index')">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

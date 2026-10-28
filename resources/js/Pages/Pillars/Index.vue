<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import DataTable from '@/Components/DataTable.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    pillars: Array,
    canManage: Boolean,
});

const showForm = ref(false);
const form = useForm({
    name: '',
    description: '',
    status: 'active',
});

const submit = () => {
    form.post(route('pillars.store'), {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};
</script>

<template>
    <Head title="Strategic Pillars" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Strategic Pillars" description="Top-level themes that group initiatives.">
                <template #actions>
                    <PrimaryButton v-if="canManage" type="button" @click="showForm = !showForm">
                        {{ showForm ? 'Cancel' : 'Add pillar' }}
                    </PrimaryButton>
                </template>
            </PageHeader>
        </template>

        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard v-if="showForm && canManage" title="New pillar">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel value="Name" />
                            <TextInput v-model="form.name" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Description" />
                            <TextareaInput v-model="form.description" :rows="3" class="mt-1" />
                        </div>
                        <PrimaryButton :disabled="form.processing">Create</PrimaryButton>
                    </form>
                </SectionCard>

                <SectionCard flush>
                    <DataTable v-if="pillars?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Pillar</th>
                                <th class="app-th">Status</th>
                                <th class="app-th">Initiatives</th>
                            </tr>
                        </template>
                        <tr v-for="pillar in pillars" :key="pillar.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('pillars.show', pillar.id)" class="app-link font-medium">{{ pillar.name }}</Link>
                                <p v-if="pillar.description" class="text-sm text-ink-muted">{{ pillar.description }}</p>
                            </td>
                            <td class="app-td"><StatusBadge :value="pillar.status" /></td>
                            <td class="app-td text-sm">{{ pillar.initiatives_count }}</td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No strategic pillars yet">
                        <template v-if="canManage" #actions>
                            <PrimaryButton type="button" @click="showForm = true">Add pillar</PrimaryButton>
                        </template>
                    </EmptyState>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

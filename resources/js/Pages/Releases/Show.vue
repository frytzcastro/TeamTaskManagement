<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import SectionCard from '@/Components/SectionCard.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    release: Object,
    items: Array,
    availableTasks: Array,
    availableFeatures: Array,
    availableBugs: Array,
    canManage: Boolean,
});

const itemForm = useForm({ item_type: 'task', item_id: '' });
const deployForm = useForm({ environment: props.release.environment || 'staging', status: 'success', reference: '', notes: '' });

const addItem = () => itemForm.post(route('releases.items.store', props.release.id), { onSuccess: () => itemForm.reset('item_id') });
const logDeploy = () => deployForm.post(route('releases.deployments.store', props.release.id));
const markDeployed = () => router.post(route('releases.mark-deployed', props.release.id));

const itemToRemove = ref(null);
const showRemoveModal = ref(false);

const requestRemoveItem = (id) => {
    itemToRemove.value = id;
    showRemoveModal.value = true;
};

const confirmRemoveItem = () => {
    router.delete(route('releases.items.destroy', [props.release.id, itemToRemove.value]));
    showRemoveModal.value = false;
    itemToRemove.value = null;
};
</script>

<template>
    <Head :title="release.name" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="release.name">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Releases', href: route('releases.index') },
                        { label: release.name },
                    ]" />
                </template>
                <template #meta>
                    <span class="text-sm text-ink-muted">{{ release.delivery_project?.name }}</span>
                    <StatusBadge :value="release.status" />
                </template>
                <template #actions>
                    <Link v-if="canManage" :href="route('releases.edit', release.id)" class="inline-flex">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                    <PrimaryButton v-if="canManage && release.status !== 'deployed'" type="button" @click="markDeployed">Mark deployed</PrimaryButton>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard title="Overview">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-ink-muted">Version</dt><dd class="font-medium text-ink">{{ release.version || '—' }}</dd></div>
                        <div><dt class="text-ink-muted">Environment</dt><dd class="font-medium text-ink">{{ release.environment }}</dd></div>
                        <div><dt class="text-ink-muted">Target date</dt><dd class="font-medium text-ink">{{ release.target_date || '—' }}</dd></div>
                        <div><dt class="text-ink-muted">Released at</dt><dd class="font-medium text-ink">{{ release.released_at || '—' }}</dd></div>
                    </dl>
                </SectionCard>

                <SectionCard title="Release items">
                    <ul v-if="items?.length" class="mb-4 divide-y border border-border rounded-lg">
                        <li v-for="i in items" :key="i.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                            <span>
                                <span class="text-xs uppercase text-ink-muted">{{ i.item_type }}</span>
                                <span class="text-ink"> · {{ i.title }}</span>
                                <StatusBadge v-if="i.status" :value="i.status" class="ms-2" />
                            </span>
                            <DangerButton v-if="canManage" type="button" @click="requestRemoveItem(i.id)">Remove</DangerButton>
                        </li>
                    </ul>
                    <EmptyState v-else title="No items in this release" description="Add tasks, features, or bugs below." class="mb-4" />
                    <form v-if="canManage" class="flex flex-wrap items-end gap-3" @submit.prevent="addItem">
                        <div>
                            <InputLabel value="Type" />
                            <SelectInput v-model="itemForm.item_type" class="mt-1">
                                <option value="task">Task</option>
                                <option value="feature">Feature</option>
                                <option value="bug">Bug</option>
                            </SelectInput>
                        </div>
                        <div class="min-w-[12rem] flex-1">
                            <InputLabel value="Item" />
                            <SelectInput v-if="itemForm.item_type === 'task'" v-model="itemForm.item_id" class="mt-1" required>
                                <option value="">Select task</option>
                                <option v-for="t in availableTasks" :key="t.id" :value="t.id">{{ t.title }}</option>
                            </SelectInput>
                            <SelectInput v-else-if="itemForm.item_type === 'feature'" v-model="itemForm.item_id" class="mt-1" required>
                                <option value="">Select feature</option>
                                <option v-for="f in availableFeatures" :key="f.id" :value="f.id">{{ f.title }}</option>
                            </SelectInput>
                            <SelectInput v-else v-model="itemForm.item_id" class="mt-1" required>
                                <option value="">Select bug</option>
                                <option v-for="b in availableBugs" :key="b.id" :value="b.id">{{ b.title }}</option>
                            </SelectInput>
                        </div>
                        <PrimaryButton :disabled="itemForm.processing">Add</PrimaryButton>
                    </form>
                </SectionCard>

                <SectionCard title="Deployments">
                    <ul v-if="release.deployments?.length" class="mb-4 divide-y border border-border rounded-lg text-sm">
                        <li v-for="d in release.deployments" :key="d.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                            <span class="text-ink-muted">{{ d.environment }} · {{ d.reference || '—' }} · {{ d.deployed_by?.name || '—' }}</span>
                            <StatusBadge :value="d.status" />
                        </li>
                    </ul>
                    <p v-else class="mb-4 text-sm text-ink-muted">No deployments logged yet.</p>
                    <form v-if="canManage" class="grid gap-4 sm:grid-cols-2" @submit.prevent="logDeploy">
                        <div>
                            <InputLabel value="Environment" />
                            <TextInput v-model="deployForm.environment" class="mt-1 block w-full" placeholder="Environment" />
                        </div>
                        <div>
                            <InputLabel value="Status" />
                            <SelectInput v-model="deployForm.status" class="mt-1">
                                <option value="pending">pending</option>
                                <option value="success">success</option>
                                <option value="failed">failed</option>
                            </SelectInput>
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel value="Reference" />
                            <TextInput v-model="deployForm.reference" class="mt-1 block w-full" placeholder="Git/ref URL" />
                        </div>
                        <div class="sm:col-span-2">
                            <PrimaryButton :disabled="deployForm.processing">Log deployment</PrimaryButton>
                        </div>
                    </form>
                </SectionCard>
            </div>
        </div>

        <ConfirmModal
            :show="showRemoveModal"
            variant="danger"
            title="Remove release item"
            message="Remove this item from the release? The underlying task, feature, or bug is not deleted."
            confirm-label="Remove"
            @close="showRemoveModal = false"
            @confirm="confirmRemoveItem"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import SectionCard from '@/Components/SectionCard.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    ticket: Object,
    canManage: Boolean,
    canUpdate: Boolean,
    canConvert: Boolean,
});

const commentForm = useForm({ body: '' });
const addComment = () => commentForm.post(route('support.comments.store', props.ticket.id), {
    onSuccess: () => commentForm.reset(),
});

const convertTarget = ref(null);
const showConvertModal = ref(false);

const requestConvert = (target) => {
    convertTarget.value = target;
    showConvertModal.value = true;
};

const confirmConvert = () => {
    router.post(route('support.convert', props.ticket.id), { target: convertTarget.value });
    showConvertModal.value = false;
    convertTarget.value = null;
};

const convertTargetLabel = () => {
    const t = convertTarget.value;
    if (t === 'bug') return 'Bug';
    if (t === 'task') return 'Task';
    if (t === 'feature') return 'Feature';
    return t || '';
};
</script>

<template>
    <Head :title="ticket.title" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="ticket.title">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Support', href: route('support.index') },
                        { label: ticket.title },
                    ]" />
                </template>
                <template #actions>
                    <Link v-if="canUpdate" :href="route('support.edit', ticket.id)" class="inline-flex">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <SectionCard class="lg:col-span-2">
                        <p class="whitespace-pre-wrap text-ink">{{ ticket.description || 'No description.' }}</p>
                    </SectionCard>
                    <SectionCard title="Details">
                        <dl class="space-y-2 text-sm">
                            <div class="flex flex-wrap items-center gap-2">
                                <dt class="text-ink-muted">Status:</dt>
                                <dd><StatusBadge :value="ticket.status" /></dd>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <dt class="text-ink-muted">Priority:</dt>
                                <dd><StatusBadge :value="ticket.priority" /></dd>
                            </div>
                            <div><span class="text-ink-muted">Category:</span> {{ ticket.category?.replaceAll('_', ' ') }}</div>
                            <div><span class="text-ink-muted">Assignee:</span> {{ ticket.assignee?.name || '—' }}</div>
                            <div><span class="text-ink-muted">System:</span> {{ ticket.system?.name || '—' }}</div>
                            <div><span class="text-ink-muted">Project:</span> {{ ticket.delivery_project?.name || '—' }}</div>
                            <div :class="ticket.response_breached ? 'text-red-700' : ''">
                                <span class="text-ink-muted">Response due:</span> {{ ticket.first_response_due_at || '—' }}
                            </div>
                            <div :class="ticket.resolution_breached ? 'text-red-700' : ''">
                                <span class="text-ink-muted">Resolve due:</span> {{ ticket.resolve_due_at || '—' }}
                            </div>
                        </dl>
                    </SectionCard>
                </div>

                <SectionCard v-if="canConvert" title="Convert ticket">
                    <p class="mb-3 text-sm text-ink-muted">Create a linked engineering record from this support ticket.</p>
                    <div class="flex flex-wrap gap-2">
                        <SecondaryButton type="button" @click="requestConvert('bug')">Convert to Bug</SecondaryButton>
                        <SecondaryButton type="button" @click="requestConvert('task')">Convert to Task</SecondaryButton>
                        <SecondaryButton type="button" @click="requestConvert('feature')">Convert to Feature</SecondaryButton>
                    </div>
                </SectionCard>

                <SectionCard v-if="ticket.status === 'converted'" title="Conversion links">
                    <ul class="space-y-2 text-sm">
                        <li v-if="ticket.converted_bug">
                            <span class="text-ink-muted">Bug:</span>
                            <Link :href="route('bugs.show', ticket.converted_bug.id)" class="app-link font-medium">{{ ticket.converted_bug.title }}</Link>
                        </li>
                        <li v-if="ticket.converted_task">
                            <span class="text-ink-muted">Task:</span>
                            <Link :href="route('tasks.show', ticket.converted_task.id)" class="app-link font-medium">{{ ticket.converted_task.title }}</Link>
                        </li>
                        <li v-if="ticket.converted_feature">
                            <span class="text-ink-muted">Feature:</span>
                            <Link :href="route('features.show', ticket.converted_feature.id)" class="app-link font-medium">{{ ticket.converted_feature.title }}</Link>
                        </li>
                    </ul>
                </SectionCard>

                <SectionCard title="Comments">
                    <ul v-if="ticket.comments?.length" class="mb-4 divide-y border border-border rounded-lg">
                        <li v-for="c in ticket.comments" :key="c.id" class="px-4 py-3 text-sm">
                            <div class="font-medium text-ink">{{ c.user?.name }}</div>
                            <div class="mt-1 text-ink-muted">{{ c.body }}</div>
                        </li>
                    </ul>
                    <p v-else class="mb-4 text-sm text-ink-muted">No comments yet.</p>
                    <form v-if="canUpdate" class="space-y-3" @submit.prevent="addComment">
                        <TextareaInput v-model="commentForm.body" :rows="2" required />
                        <PrimaryButton :disabled="commentForm.processing">Add comment</PrimaryButton>
                    </form>
                </SectionCard>
            </div>
        </div>

        <ConfirmModal
            :show="showConvertModal"
            title="Convert ticket"
            :message="`Create a new ${convertTargetLabel()} from this support ticket? The ticket will be marked converted.`"
            confirm-label="Convert"
            @close="showConvertModal = false"
            @confirm="confirmConvert"
        />
    </AuthenticatedLayout>
</template>

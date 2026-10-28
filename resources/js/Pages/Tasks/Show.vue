<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import SectionCard from '@/Components/SectionCard.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    task: Object,
    statuses: Array,
    canUpdate: Boolean,
    canManage: Boolean,
});

const statusForm = useForm({
    status: props.task.status,
    estimate: props.task.estimate,
    start_date: props.task.start_date?.substring?.(0, 10) || props.task.start_date || '',
    due_date: props.task.due_date?.substring?.(0, 10) || props.task.due_date || '',
    actual_effort: props.task.actual_effort,
    completion_percent: props.task.completion_percent,
    blockers: props.task.blockers,
    git_reference: props.task.git_reference,
    description: props.task.description,
});

const commentForm = useForm({ body: '' });

const updateTask = () => statusForm.put(route('tasks.update', props.task.id));
const addComment = () => {
    commentForm.post(route('tasks.comments.store', props.task.id), {
        onSuccess: () => commentForm.reset(),
    });
};
</script>

<template>
    <Head :title="task.title" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="task.title">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Tasks', href: route('tasks.index') },
                        { label: task.title },
                    ]" />
                </template>
                <template #actions>
                    <Link
                        :href="route('bugs.create', { task_id: task.id, delivery_project_id: task.delivery_project_id })"
                        class="inline-flex"
                    >
                        <SecondaryButton type="button">Report Bug</SecondaryButton>
                    </Link>
                    <Link v-if="canManage" :href="route('tasks.edit', task.id)" class="inline-flex">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <SectionCard class="lg:col-span-2">
                        <p class="whitespace-pre-wrap text-ink">{{ task.description || 'No description.' }}</p>
                        <p class="mt-3 text-sm text-ink-muted">
                            Delivery project:
                            <Link :href="route('delivery-projects.show', task.delivery_project_id)" class="app-link font-medium">
                                {{ task.delivery_project?.name }}
                            </Link>
                        </p>
                    </SectionCard>
                    <SectionCard title="Details">
                        <dl class="space-y-2 text-sm">
                            <div class="flex flex-wrap items-center gap-2">
                                <dt class="text-ink-muted">Status:</dt>
                                <dd><StatusBadge :value="task.status" /></dd>
                            </div>
                            <div><span class="text-ink-muted">Assignee:</span> {{ task.assignee?.name || '—' }}</div>
                            <div><span class="text-ink-muted">Commitment window:</span> {{ task.start_date || '—' }} → {{ task.due_date || '—' }}</div>
                            <div><span class="text-ink-muted">Estimate:</span> {{ task.estimate ?? '—' }} working days</div>
                            <div><span class="text-ink-muted">Actual:</span> {{ task.actual_effort ?? '—' }} · Started: {{ task.started_at || '—' }} · Completed: {{ task.completed_at || '—' }}</div>
                            <div><span class="text-ink-muted">Completion:</span> {{ task.completion_percent }}%</div>
                        </dl>
                    </SectionCard>
                </div>

                <SectionCard v-if="canUpdate" title="Update progress">
                    <form @submit.prevent="updateTask" class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <InputLabel value="Status" />
                                <SelectInput v-model="statusForm.status" class="mt-1">
                                    <option v-for="s in statuses" :key="s" :value="s">{{ s.replaceAll('_', ' ') }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="statusForm.errors.status" />
                            </div>
                            <div>
                                <InputLabel value="Commitment estimate (working days)" />
                                <TextInput v-model="statusForm.estimate" type="number" step="0.5" min="0" class="mt-1 block w-full" />
                                <InputError class="mt-1" :message="statusForm.errors.estimate" />
                            </div>
                            <div>
                                <InputLabel value="Actual effort (working days)" />
                                <TextInput v-model="statusForm.actual_effort" type="number" step="0.5" class="mt-1 block w-full" />
                                <InputError class="mt-1" :message="statusForm.errors.actual_effort" />
                            </div>
                            <div>
                                <InputLabel value="Start date" />
                                <TextInput v-model="statusForm.start_date" type="date" class="mt-1 block w-full" />
                                <InputError class="mt-1" :message="statusForm.errors.start_date" />
                            </div>
                            <div>
                                <InputLabel value="Due date" />
                                <TextInput v-model="statusForm.due_date" type="date" class="mt-1 block w-full" />
                                <InputError class="mt-1" :message="statusForm.errors.due_date" />
                            </div>
                            <div>
                                <InputLabel value="Completion %" />
                                <TextInput v-model="statusForm.completion_percent" type="number" min="0" max="100" class="mt-1 block w-full" />
                                <InputError class="mt-1" :message="statusForm.errors.completion_percent" />
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Blockers" />
                            <TextareaInput v-model="statusForm.blockers" :rows="2" class="mt-1" />
                            <InputError class="mt-1" :message="statusForm.errors.blockers" />
                        </div>
                        <div>
                            <InputLabel value="Git / PR reference" />
                            <TextInput v-model="statusForm.git_reference" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="statusForm.errors.git_reference" />
                        </div>
                        <PrimaryButton :disabled="statusForm.processing">Save updates</PrimaryButton>
                    </form>
                </SectionCard>

                <SectionCard title="Comments">
                    <ul v-if="task.comments?.length" class="divide-y divide-border">
                        <li v-for="c in task.comments" :key="c.id" class="py-3 text-sm first:pt-0">
                            <div class="font-medium text-ink">{{ c.user?.name }}</div>
                            <div class="text-ink-muted">{{ c.body }}</div>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-ink-muted">No comments yet.</p>
                    <form @submit.prevent="addComment" class="mt-4 space-y-2 border-t border-border pt-4">
                        <TextareaInput v-model="commentForm.body" :rows="2" required placeholder="Add a comment" />
                        <InputError :message="commentForm.errors.body" />
                        <PrimaryButton :disabled="commentForm.processing">Comment</PrimaryButton>
                    </form>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

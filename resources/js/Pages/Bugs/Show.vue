<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import SectionCard from '@/Components/SectionCard.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    bug: Object,
    statuses: Array,
    canUpdate: Boolean,
    canManage: Boolean,
});

const statusForm = useForm({
    status: props.bug.status,
    severity: props.bug.severity,
    description: props.bug.description,
});

const updateBug = () => statusForm.put(route('bugs.update', props.bug.id));
</script>

<template>
    <Head :title="bug.title" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="bug.title">
                <template #breadcrumbs>
                    <Breadcrumbs :items="[
                        { label: 'Bugs', href: route('bugs.index') },
                        { label: bug.title },
                    ]" />
                </template>
                <template #actions>
                    <Link v-if="canManage" :href="route('bugs.edit', bug.id)" class="inline-flex">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <SectionCard class="lg:col-span-2">
                        <p class="whitespace-pre-wrap text-ink">{{ bug.description || 'No description.' }}</p>
                        <div v-if="bug.steps_to_reproduce" class="mt-4 text-sm">
                            <strong class="text-ink">Steps:</strong>
                            <pre class="mt-1 whitespace-pre-wrap text-ink-muted">{{ bug.steps_to_reproduce }}</pre>
                        </div>
                        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                            <div><strong class="text-ink">Expected:</strong> <span class="text-ink-muted">{{ bug.expected_result || '—' }}</span></div>
                            <div><strong class="text-ink">Actual:</strong> <span class="text-ink-muted">{{ bug.actual_result || '—' }}</span></div>
                        </div>
                        <p v-if="bug.delivery_project_id" class="mt-4 text-sm text-ink-muted">
                            Delivery project:
                            <Link :href="route('delivery-projects.show', bug.delivery_project_id)" class="app-link font-medium">
                                {{ bug.delivery_project?.name }}
                            </Link>
                        </p>
                        <p v-if="bug.task_id" class="mt-2 text-sm text-ink-muted">
                            Related task:
                            <Link :href="route('tasks.show', bug.task_id)" class="app-link font-medium">
                                {{ bug.task?.title }}
                            </Link>
                        </p>
                        <p v-if="bug.system_id" class="mt-2 text-sm text-ink-muted">
                            System:
                            <Link :href="route('systems.show', bug.system_id)" class="app-link font-medium">
                                {{ bug.system?.name }}
                            </Link>
                        </p>
                    </SectionCard>
                    <SectionCard title="Details">
                        <dl class="space-y-2 text-sm">
                            <div class="flex flex-wrap items-center gap-2">
                                <dt class="text-ink-muted">Status:</dt>
                                <dd><StatusBadge :value="bug.status" /></dd>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <dt class="text-ink-muted">Severity:</dt>
                                <dd><StatusBadge :value="bug.severity" /></dd>
                            </div>
                            <div><span class="text-ink-muted">Environment:</span> <span class="capitalize">{{ bug.environment }}</span></div>
                            <div><span class="text-ink-muted">Reporter:</span> {{ bug.reporter?.name || '—' }}</div>
                            <div><span class="text-ink-muted">Assignee:</span> {{ bug.assignee?.name || '—' }}</div>
                            <div><span class="text-ink-muted">Reopened:</span> {{ bug.reopened_count }}</div>
                        </dl>
                    </SectionCard>
                </div>

                <SectionCard v-if="canUpdate" title="Update status">
                    <form @submit.prevent="updateBug" class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Status" />
                                <SelectInput v-model="statusForm.status" class="mt-1">
                                    <option v-for="s in statuses" :key="s" :value="s">{{ s.replaceAll('_', ' ') }}</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="statusForm.errors.status" />
                            </div>
                            <div>
                                <InputLabel value="Severity" />
                                <SelectInput v-model="statusForm.severity" class="mt-1">
                                    <option value="critical">critical</option>
                                    <option value="high">high</option>
                                    <option value="medium">medium</option>
                                    <option value="low">low</option>
                                </SelectInput>
                                <InputError class="mt-1" :message="statusForm.errors.severity" />
                            </div>
                        </div>
                        <PrimaryButton :disabled="statusForm.processing">Save</PrimaryButton>
                    </form>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

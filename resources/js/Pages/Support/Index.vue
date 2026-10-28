<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FilterBar from '@/Components/FilterBar.vue';
import SectionCard from '@/Components/SectionCard.vue';
import DataTable from '@/Components/DataTable.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    tickets: Object,
    users: Array,
    filters: Object,
    statuses: Array,
    priorities: Array,
    canManage: Boolean,
    canCreate: Boolean,
    canManageSla: Boolean,
    slas: Array,
});

const slaForm = useForm({
    slas: (props.slas || []).map((s) => ({
        priority: s.priority,
        response_minutes: s.response_minutes,
        resolution_minutes: s.resolution_minutes,
    })),
});

const filter = (key, value) => {
    router.get(route('support.index'), { ...props.filters, [key]: value || undefined }, { preserveState: true });
};

const filterSelectClass = 'rounded-md border-border text-sm shadow-sm focus:border-brand focus:ring-brand';
</script>

<template>
    <Head title="Support" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Support" description="Customer/ops intake separate from engineering bugs. Convert to Bug, Task, or Feature when needed.">
                <template #actions>
                    <Link v-if="canCreate" :href="route('support.create')">
                        <PrimaryButton type="button">New ticket</PrimaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard>
                    <FilterBar>
                        <select :value="filters.status || ''" :class="filterSelectClass" @change="filter('status', $event.target.value)">
                            <option value="">All statuses</option>
                            <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                        </select>
                        <select :value="filters.priority || ''" :class="filterSelectClass" @change="filter('priority', $event.target.value)">
                            <option value="">All priorities</option>
                            <option v-for="p in priorities" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </FilterBar>
                </SectionCard>

                <SectionCard flush>
                    <DataTable v-if="tickets.data?.length">
                        <template #head>
                            <tr>
                                <th class="app-th">Ticket</th>
                                <th class="app-th">Priority</th>
                                <th class="app-th">Status</th>
                                <th class="app-th">Assignee</th>
                                <th class="app-th">SLA</th>
                            </tr>
                        </template>
                        <tr v-for="t in tickets.data" :key="t.id" class="hover:bg-slate-50">
                            <td class="app-td">
                                <Link :href="route('support.show', t.id)" class="app-link font-medium">{{ t.title }}</Link>
                                <div class="text-xs capitalize text-ink-muted">{{ t.category?.replaceAll('_', ' ') }}</div>
                            </td>
                            <td class="app-td"><StatusBadge :value="t.priority" /></td>
                            <td class="app-td"><StatusBadge :value="t.status" /></td>
                            <td class="app-td text-ink-muted">{{ t.assignee?.name || '—' }}</td>
                            <td class="app-td text-xs">
                                <span v-if="t.response_breached || t.resolution_breached" class="font-medium text-red-700">Breached</span>
                                <span v-else class="text-ink-muted">OK</span>
                            </td>
                        </tr>
                    </DataTable>
                    <EmptyState v-else title="No support tickets" description="Adjust filters or create a ticket for customer or ops intake." />
                    <template v-if="tickets.data?.length" #footer>
                        <Pagination :links="tickets" />
                    </template>
                </SectionCard>

                <SectionCard v-if="canManageSla" title="SLA matrix (minutes)">
                    <form class="space-y-4" @submit.prevent="slaForm.put(route('support.slas.update'))">
                        <div class="hidden gap-3 text-xs font-medium uppercase tracking-wide text-ink-muted sm:grid sm:grid-cols-3">
                            <div>Priority</div>
                            <div>Response</div>
                            <div>Resolution</div>
                        </div>
                        <div v-for="(row, i) in slaForm.slas" :key="row.priority" class="grid gap-3 sm:grid-cols-3">
                            <div class="flex items-center text-sm font-medium capitalize text-ink">{{ row.priority }}</div>
                            <div>
                                <InputLabel value="Response (min)" class="sm:sr-only" />
                                <TextInput v-model.number="slaForm.slas[i].response_minutes" type="number" min="1" class="block w-full" placeholder="Response" />
                            </div>
                            <div>
                                <InputLabel value="Resolution (min)" class="sm:sr-only" />
                                <TextInput v-model.number="slaForm.slas[i].resolution_minutes" type="number" min="1" class="block w-full" placeholder="Resolution" />
                            </div>
                        </div>
                        <PrimaryButton :disabled="slaForm.processing">Save SLAs</PrimaryButton>
                    </form>
                </SectionCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ScheduleGantt from '@/Components/ScheduleGantt.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FilterBar from '@/Components/FilterBar.vue';
import StatCard from '@/Components/StatCard.vue';
import SectionCard from '@/Components/SectionCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { CHART_COLORS } from '@/Lib/statusColors.js';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    schedule: Object,
    deliveryProjects: Array,
    users: Array,
    viewMode: { type: String, default: 'Month' },
});

const filters = reactive({
    from: props.schedule?.filters?.from || props.schedule?.window?.from || '',
    to: props.schedule?.filters?.to || props.schedule?.window?.to || '',
    project_id: props.schedule?.filters?.project_id || '',
    assignee_id: props.schedule?.filters?.assignee_id || '',
    view: props.viewMode || 'Month',
});

const apply = () => {
    router.get(route('schedule.index'), {
        from: filters.from || undefined,
        to: filters.to || undefined,
        project_id: filters.project_id || undefined,
        assignee_id: filters.assignee_id || undefined,
        view: filters.view || 'Month',
    }, { preserveState: true });
};
</script>

<template>
    <Head title="Schedule" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="Schedule"
                description="Portfolio delivery Gantt — planned vs rolled envelopes; slip and assignee overlap."
            />
        </template>
        <div class="py-6 sm:py-8">
            <div class="app-page space-y-6">
                <SectionCard>
                    <form @submit.prevent="apply">
                        <FilterBar class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-6">
                            <div>
                                <InputLabel value="From" />
                                <TextInput v-model="filters.from" type="date" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="To" />
                                <TextInput v-model="filters.to" type="date" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel value="Project" />
                                <SelectInput v-model="filters.project_id" class="mt-1">
                                    <option value="">All (summary)</option>
                                    <option v-for="p in deliveryProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Assignee" />
                                <SelectInput v-model="filters.assignee_id" class="mt-1">
                                    <option value="">All</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel value="Zoom" />
                                <SelectInput v-model="filters.view" class="mt-1">
                                    <option value="Week">Week</option>
                                    <option value="Month">Month</option>
                                    <option value="Year">Year</option>
                                </SelectInput>
                            </div>
                            <div class="flex items-end">
                                <PrimaryButton type="submit" class="w-full justify-center">Apply</PrimaryButton>
                            </div>
                        </FilterBar>
                    </form>
                </SectionCard>

                <div class="grid gap-4 sm:grid-cols-3">
                    <StatCard label="Bars" :value="schedule.bars?.length || 0" />
                    <StatCard label="Schedule slip signals" :value="schedule.conflicts?.slip_count || 0" />
                    <StatCard label="Overlap pairs (approx.)" :value="schedule.conflicts?.overlap_count || 0" />
                </div>

                <div class="flex flex-wrap gap-4 text-xs text-ink-muted">
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded" :style="{ backgroundColor: CHART_COLORS.brand }" /> Project</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded" :style="{ backgroundColor: CHART_COLORS.info }" /> Epic</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded" :style="{ backgroundColor: CHART_COLORS.success }" /> Feature</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded" :style="{ backgroundColor: CHART_COLORS.muted }" /> Task</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded" :style="{ backgroundColor: CHART_COLORS.warning }" /> Slip / overdue</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded border-2" :style="{ borderColor: CHART_COLORS.overlap }" /> Overlap</span>
                </div>

                <p v-if="!filters.project_id" class="text-sm text-ink-muted">
                    Showing project summaries. Choose a project to expand Epic → Feature → Task, or open a project hub Schedule tab.
                </p>

                <SectionCard title="Timeline" flush :padding="false">
                    <ScheduleGantt :bars="schedule.bars" :view-mode="filters.view" />
                </SectionCard>

                <div class="grid gap-6 lg:grid-cols-2">
                    <SectionCard title="Undated work">
                        <ul v-if="schedule.undated?.length" class="divide-y divide-border">
                            <li v-for="item in schedule.undated" :key="item.id" class="flex justify-between gap-2 py-3 text-sm first:pt-0 last:pb-0">
                                <div>
                                    <span class="text-xs uppercase text-ink-muted">{{ item.type }}</span>
                                    <Link :href="item.href" class="app-link ml-2 font-medium">{{ item.title }}</Link>
                                </div>
                                <span class="text-ink-muted">{{ item.assignee_name || item.status || '—' }}</span>
                            </li>
                        </ul>
                        <EmptyState v-else title="No undated items" description="No undated items in this view." />
                    </SectionCard>
                    <SectionCard title="Tips">
                        <ul class="space-y-2 text-sm text-ink-muted">
                            <li>Bars use rolled envelope when present, otherwise planned/commitment dates.</li>
                            <li>Amber = slip (rolled past planned, or overdue / late complete).</li>
                            <li>Violet stroke = same assignee with overlapping open tasks.</li>
                            <li>Click a bar to open that record.</li>
                        </ul>
                    </SectionCard>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

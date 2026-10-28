<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ScheduleGantt from '@/Components/ScheduleGantt.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Separator } from '@/Components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { CHART_COLORS } from '@/Lib/statusColors.js';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const ALL = '__all__';

const props = defineProps({
    schedule: Object,
    deliveryProjects: Array,
    users: Array,
    viewMode: { type: String, default: 'Month' },
});

const filters = reactive({
    from: props.schedule?.filters?.from || props.schedule?.window?.from || '',
    to: props.schedule?.filters?.to || props.schedule?.window?.to || '',
    project_id: props.schedule?.filters?.project_id
        ? String(props.schedule.filters.project_id)
        : ALL,
    assignee_id: props.schedule?.filters?.assignee_id
        ? String(props.schedule.filters.assignee_id)
        : ALL,
    view: props.viewMode || 'Month',
});

const showingProjectSummary = computed(() => filters.project_id === ALL);

const metrics = computed(() => [
    { label: 'Bars', value: props.schedule?.bars?.length || 0 },
    { label: 'Schedule slip signals', value: props.schedule?.conflicts?.slip_count || 0 },
    { label: 'Overlap pairs (approx.)', value: props.schedule?.conflicts?.overlap_count || 0 },
]);

const legend = [
    { label: 'Project', color: CHART_COLORS.brand },
    { label: 'Epic', color: CHART_COLORS.info },
    { label: 'Feature', color: CHART_COLORS.success },
    { label: 'Task', color: CHART_COLORS.muted },
    { label: 'Slip / overdue', color: CHART_COLORS.warning },
    { label: 'Overlap', color: CHART_COLORS.overlap, outline: true },
];

const tips = [
    'Bars use rolled envelope when present, otherwise planned/commitment dates.',
    'Amber = slip (rolled past planned, or overdue / late complete).',
    'Violet stroke = same assignee with overlapping open tasks.',
    'Click a bar to open that record.',
];

const apply = () => {
    router.get(route('schedule.index'), {
        from: filters.from || undefined,
        to: filters.to || undefined,
        project_id: filters.project_id === ALL ? undefined : filters.project_id,
        assignee_id: filters.assignee_id === ALL ? undefined : filters.assignee_id,
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
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Filters</CardTitle>
                        <CardDescription>
                            Narrow the portfolio window, project, assignee, and Gantt zoom.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="apply">
                            <div class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-6">
                                <div class="space-y-1.5">
                                    <Label for="schedule-from">From</Label>
                                    <Input
                                        id="schedule-from"
                                        v-model="filters.from"
                                        type="date"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="schedule-to">To</Label>
                                    <Input
                                        id="schedule-to"
                                        v-model="filters.to"
                                        type="date"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Project</Label>
                                    <Select v-model="filters.project_id">
                                        <SelectTrigger aria-label="Project">
                                            <SelectValue placeholder="All (summary)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="ALL">All (summary)</SelectItem>
                                            <SelectItem
                                                v-for="p in deliveryProjects"
                                                :key="p.id"
                                                :value="String(p.id)"
                                            >
                                                {{ p.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Assignee</Label>
                                    <Select v-model="filters.assignee_id">
                                        <SelectTrigger aria-label="Assignee">
                                            <SelectValue placeholder="All" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="ALL">All</SelectItem>
                                            <SelectItem
                                                v-for="u in users"
                                                :key="u.id"
                                                :value="String(u.id)"
                                            >
                                                {{ u.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Zoom</Label>
                                    <Select v-model="filters.view">
                                        <SelectTrigger aria-label="Zoom">
                                            <SelectValue placeholder="Month" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Week">Week</SelectItem>
                                            <SelectItem value="Month">Month</SelectItem>
                                            <SelectItem value="Year">Year</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="flex items-end">
                                    <Button type="submit" class="w-full">Apply</Button>
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <div class="grid gap-4 sm:grid-cols-3">
                    <Card v-for="metric in metrics" :key="metric.label">
                        <CardHeader class="p-4 pb-2">
                            <CardDescription class="text-[11px] font-semibold uppercase tracking-[0.08em]">
                                {{ metric.label }}
                            </CardDescription>
                            <CardTitle class="text-2xl tabular-nums">
                                {{ metric.value }}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Badge
                        v-for="item in legend"
                        :key="item.label"
                        variant="outline"
                        class="gap-1.5 font-normal text-muted-foreground"
                    >
                        <span
                            class="inline-block h-2.5 w-2.5 shrink-0 rounded-sm"
                            :class="item.outline ? 'border-2 bg-transparent' : ''"
                            :style="item.outline
                                ? { borderColor: item.color }
                                : { backgroundColor: item.color }"
                        />
                        {{ item.label }}
                    </Badge>
                </div>

                <Alert v-if="showingProjectSummary">
                    <AlertDescription>
                        Showing project summaries. Choose a project to expand Epic → Feature → Task, or open a project hub Schedule tab.
                    </AlertDescription>
                </Alert>

                <Card>
                    <CardHeader class="border-b border-border/70 px-4 py-3 sm:px-5">
                        <CardTitle class="text-base">Timeline</CardTitle>
                        <CardDescription>
                            Click a bar to open that record.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <ScheduleGantt :bars="schedule.bars" :view-mode="filters.view" />
                    </CardContent>
                </Card>

                <div class="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader class="border-b border-border/70 px-4 py-3 sm:px-5">
                            <CardTitle class="text-base">Undated work</CardTitle>
                            <CardDescription>
                                Items in this view without display dates.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Title</TableHead>
                                        <TableHead class="text-right">Assignee / status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <template v-if="schedule.undated?.length">
                                        <TableRow v-for="item in schedule.undated" :key="item.id">
                                            <TableCell>
                                                <Badge variant="secondary" class="font-normal uppercase">
                                                    {{ item.type }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    variant="link"
                                                    class="h-auto p-0 font-medium"
                                                    as-child
                                                >
                                                    <Link :href="item.href">{{ item.title }}</Link>
                                                </Button>
                                            </TableCell>
                                            <TableCell class="text-right text-muted-foreground">
                                                {{ item.assignee_name || item.status || '—' }}
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                    <TableEmpty v-else :colspan="3">
                                        <div class="space-y-1 text-center">
                                            <p class="font-medium text-foreground">No undated items</p>
                                            <p class="text-muted-foreground">No undated items in this view.</p>
                                        </div>
                                    </TableEmpty>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="border-b border-border/70 px-4 py-3 sm:px-5">
                            <CardTitle class="text-base">Tips</CardTitle>
                            <CardDescription>
                                How to read slip, overlap, and bar dates.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-4 sm:p-5">
                            <ul class="space-y-0">
                                <li
                                    v-for="(tip, index) in tips"
                                    :key="tip"
                                >
                                    <p class="py-2.5 text-sm text-muted-foreground">
                                        {{ tip }}
                                    </p>
                                    <Separator v-if="index < tips.length - 1" />
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

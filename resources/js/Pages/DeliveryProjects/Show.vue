<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import ImprovementWorkspace from '@/Components/ImprovementWorkspace.vue';
import ScheduleGantt from '@/Components/ScheduleGantt.vue';
import TaskKanban from '@/Components/TaskKanban.vue';
import AvatarStack from '@/Components/AvatarStack.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import AppTabs from '@/Components/AppTabs.vue';
import SectionCard from '@/Components/SectionCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { phaseLabel } from '@/Lib/improvementPhases';
import { formatStatusLabel } from '@/Lib/statusColors';

const props = defineProps({
    deliveryProject: Object,
    expectations: { type: Array, default: () => [] },
    expectationMovement: { type: Object, default: () => ({ counts: {}, next: null, target_completion: null }) },
    assignableFeatures: { type: Array, default: () => [] },
    planEpics: { type: Array, default: () => [] },
    canManage: Boolean,
    canViewDmaic: Boolean,
    canManageDmaic: Boolean,
    canApproveGate: Boolean,
    canManageReleases: Boolean,
    canManageSprints: Boolean,
    canManageEpics: Boolean,
    canManageFeatures: Boolean,
    canManageTasks: Boolean,
    canManageKpis: Boolean,
    canManageProblems: Boolean,
    canViewBugs: Boolean,
    canViewSupport: Boolean,
    improvementFeatures: Array,
    hub: Object,
    nextSteps: Array,
    schedule: Object,
    board: Array,
});

const showCustomExpectation = ref(false);

const tabFromUrl = () => {
    try {
        return new URLSearchParams(window.location.search).get('tab') || 'overview';
    } catch {
        return 'overview';
    }
};

const activeTab = ref(tabFromUrl());

watch(activeTab, (tab) => {
    const url = new URL(window.location.href);
    if (tab === 'overview') url.searchParams.delete('tab');
    else url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
});

const tabs = computed(() => {
    const list = [
        { id: 'overview', label: 'Overview' },
        { id: 'plan', label: 'Plan' },
        { id: 'build', label: 'Build' },
        { id: 'schedule', label: 'Schedule' },
        { id: 'quality', label: 'Quality' },
        { id: 'ship', label: 'Ship' },
    ];
    if (props.deliveryProject.type === 'dmaic') {
        list.push({ id: 'improve', label: 'Improve' });
    }
    return list;
});

const scheduleView = ref('Month');
const buildView = ref('list');

const projectId = computed(() => props.deliveryProject.id);

const teamPeople = computed(() => {
    const seen = new Set();
    const people = [];
    const push = (user) => {
        if (!user?.id || seen.has(user.id)) return;
        seen.add(user.id);
        people.push({ id: user.id, name: user.name });
    };
    push(props.deliveryProject.project_owner);
    push(props.deliveryProject.product_owner);
    for (const m of props.deliveryProject.members || []) push(m);
    return people;
});

const tasksByStatus = computed(() => {
    const groups = {};
    for (const task of props.deliveryProject.tasks || []) {
        if (task.parent_id) continue;
        const key = task.status || 'backlog';
        if (!groups[key]) groups[key] = [];
        groups[key].push(task);
    }
    const order = ['backlog', 'ready', 'in_progress', 'code_review', 'qa', 'qa_failed', 'blocked', 'ready_for_release', 'done', 'released'];
    return order
        .filter((status) => groups[status]?.length)
        .map((status) => ({ status, label: formatStatusLabel(status), tasks: groups[status] }))
        .concat(
            Object.keys(groups)
                .filter((status) => !order.includes(status))
                .map((status) => ({ status, label: formatStatusLabel(status), tasks: groups[status] })),
        );
});

const onBoardUpdated = () => {
    router.reload({ only: ['board', 'deliveryProject', 'hub'] });
};

const groupTone = (status) => {
    const map = {
        backlog: 'bg-slate-50',
        ready: 'bg-brand-soft/50',
        in_progress: 'bg-info-soft/70',
        code_review: 'bg-brand-soft/40',
        qa: 'bg-brand-soft/40',
        qa_failed: 'bg-danger-soft/60',
        blocked: 'bg-danger-soft/50',
        ready_for_release: 'bg-success-soft/70',
        done: 'bg-success-soft/60',
        released: 'bg-success-soft/60',
    };
    return map[status] || 'bg-app';
};

const groupDot = (status) => {
    const map = {
        backlog: 'bg-slate-400',
        ready: 'bg-brand',
        in_progress: 'bg-info',
        code_review: 'bg-brand',
        qa: 'bg-brand',
        qa_failed: 'bg-danger',
        blocked: 'bg-danger',
        ready_for_release: 'bg-success',
        done: 'bg-success',
        released: 'bg-success',
    };
    return map[status] || 'bg-ink-muted';
};

const createLinks = computed(() => {
    const id = projectId.value;
    const firstSystemId = (props.deliveryProject.initiative?.systems || [])[0]?.id
        || props.deliveryProject.features?.[0]?.system_id
        || null;
    return {
        epic: firstSystemId ? route('epics.create', { system_id: firstSystemId }) : route('epics.create'),
        feature: firstSystemId
            ? route('features.create', { system_id: firstSystemId, delivery_project_id: id, return_tab: 'plan' })
            : route('features.create', { delivery_project_id: id, return_tab: 'plan' }),
        task: route('tasks.create', { delivery_project_id: id, return_tab: 'build' }),
        sprint: route('sprints.create', { delivery_project_id: id, return_tab: 'build' }),
        bug: route('bugs.create', { delivery_project_id: id, return_tab: 'quality' }),
        support: route('support.create'),
        release: route('releases.create', { delivery_project_id: id, return_tab: 'ship' }),
        problem: route('business-problems.create', {
            initiative_id: props.deliveryProject.initiative_id,
            return_project_id: id,
            return_tab: 'improve',
        }),
        kpi: props.deliveryProject.kpis?.[0]
            ? route('kpis.show', props.deliveryProject.kpis[0].id)
            : null,
    };
});

const derivedSystems = computed(() => {
    const fromInitiative = props.deliveryProject.initiative?.systems || [];
    if (fromInitiative.length) return fromInitiative;
    const map = new Map();
    for (const f of props.deliveryProject.features || []) {
        if (f.system) map.set(f.system.id, f.system);
    }
    return [...map.values()];
});

const assignFeatureId = ref('');
const assignFeature = () => {
    if (!assignFeatureId.value) return;
    router.post(route('features.assign', assignFeatureId.value), {
        delivery_project_id: projectId.value,
    }, { preserveScroll: true, onSuccess: () => { assignFeatureId.value = ''; } });
};
const unassignFeature = (featureId) => {
    router.post(route('features.unassign', featureId), {}, { preserveScroll: true });
};

const milestoneForm = useForm({
    title: '',
    due_date: '',
    status: 'pending',
    description: '',
});

const addMilestone = () => {
    milestoneForm.post(route('delivery-projects.milestones.store', props.deliveryProject.id), {
        onSuccess: () => {
            milestoneForm.reset();
            showCustomExpectation.value = false;
        },
    });
};

const movementCounts = computed(() => props.expectationMovement?.counts || {});
const nextExpectation = computed(() => props.expectationMovement?.next || null);

const bootstrapDmaic = () => {
    router.post(route('delivery-projects.dmaic.bootstrap', props.deliveryProject.id));
};

const runStep = (step) => {
    if (step.action === 'bootstrap_dmaic') {
        bootstrapDmaic();
        return;
    }
    if (step.tab) activeTab.value = step.tab;
    if (step.href) router.visit(step.href);
};

const openBugs = computed(() =>
    (props.deliveryProject.bugs || []).filter((b) => !['resolved', 'closed'].includes(b.status)),
);
const openSupport = computed(() =>
    (props.deliveryProject.support_tickets || []).filter((t) => !['resolved', 'closed', 'converted'].includes(t.status)),
);
const readyTasks = computed(() =>
    (props.deliveryProject.tasks || []).filter((t) => t.status === 'ready_for_release'),
);

const typeLabel = computed(() =>
    props.deliveryProject.type === 'dmaic' ? 'Improvement project' : 'Delivery project',
);

const crumbs = computed(() => [
    { label: 'Projects', href: route('delivery-projects.index') },
    { label: props.deliveryProject.name },
]);

/** Plan tab: system epics that have features assigned to this project. */
const planGroups = computed(() => {
    const epics = props.planEpics?.length
        ? props.planEpics
        : [...new Map((props.deliveryProject.features || [])
            .filter((f) => f.epic)
            .map((f) => [f.epic.id, f.epic])).values()];
    const features = props.deliveryProject.features || [];
    const byEpic = new Map(epics.map((e) => [e.id, { epic: e, features: [] }]));
    const unlinked = [];
    for (const f of features) {
        if (f.epic_id && byEpic.has(f.epic_id)) byEpic.get(f.epic_id).features.push(f);
        else if (f.epic_id && f.epic) {
            if (!byEpic.has(f.epic_id)) byEpic.set(f.epic_id, { epic: f.epic, features: [] });
            byEpic.get(f.epic_id).features.push(f);
        } else unlinked.push(f);
    }
    return { groups: [...byEpic.values()], unlinked };
});

const tabLabels = computed(() =>
    tabs.value.map((t) => ({
        id: t.id,
        label: t.id === 'quality' && props.hub?.open_bugs_count
            ? `${t.label} (${props.hub.open_bugs_count})`
            : t.id === 'ship' && props.hub?.ready_for_release_count
                ? `${t.label} (${props.hub.ready_for_release_count})`
                : t.label,
    })),
);

const dodProgress = (feature) => {
    const items = feature?.dod_checklist || [];
    if (!items.length) return { done: 0, total: 0, pct: 0 };
    const done = items.filter((i) => i.completed).length;
    return { done, total: items.length, pct: Math.round((done / items.length) * 100) };
};

const severityOrder = { critical: 0, high: 1, medium: 2, low: 3 };
const sortedOpenBugs = computed(() =>
    [...openBugs.value].sort((a, b) => (severityOrder[a.severity] ?? 9) - (severityOrder[b.severity] ?? 9)),
);
const sortedOpenSupport = computed(() =>
    [...openSupport.value].sort((a, b) => (severityOrder[a.priority] ?? 9) - (severityOrder[b.priority] ?? 9)),
);
</script>

<template>
    <Head :title="deliveryProject.name" />
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="deliveryProject.name" :description="typeLabel">
                <template #breadcrumbs>
                    <Breadcrumbs :items="crumbs" />
                </template>
                <template #meta>
                    <StatusBadge :value="deliveryProject.health" />
                    <span class="app-meta">{{ formatStatusLabel(deliveryProject.status) }}</span>
                    <span v-if="deliveryProject.code" class="app-meta">{{ deliveryProject.code }}</span>
                    <template v-if="deliveryProject.initiative">
                        <span class="text-slate-300" aria-hidden="true">·</span>
                        <Link
                            :href="route('initiatives.show', deliveryProject.initiative_id)"
                            class="app-meta hover:text-brand"
                        >
                            {{ deliveryProject.initiative.name }}
                        </Link>
                    </template>
                </template>
                <template #actions>
                    <AvatarStack :people="teamPeople" :max="5" />
                    <Link v-if="canManage" :href="route('delivery-projects.edit', deliveryProject.id)">
                        <SecondaryButton type="button">Edit</SecondaryButton>
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="pb-10 pt-2">
            <div class="app-page space-y-5">
                <AppTabs v-model="activeTab" :tabs="tabLabels" />

                <!-- Overview: Attention → At a glance → Purpose → Track -->
                <div v-show="activeTab === 'overview'" class="space-y-8">
                    <!-- Zone 1: Attention -->
                    <div
                        v-if="nextSteps?.length || (deliveryProject.type === 'dmaic' && (deliveryProject.dmaic_profile?.business_problem || canViewDmaic))"
                        class="hub-zone"
                    >
                        <div class="hub-zone-label">Attention</div>

                        <div
                            v-if="nextSteps?.length"
                            class="rounded-card border border-brand/20 bg-brand-soft/35 px-4 py-4 sm:px-5"
                        >
                            <div class="mb-2 text-xs font-semibold text-brand">Recommended next</div>
                            <ul class="divide-y divide-brand/10">
                                <li
                                    v-for="step in nextSteps"
                                    :key="step.key"
                                    class="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-ink">{{ step.title }}</div>
                                        <div class="mt-0.5 text-sm text-ink-muted">
                                            {{ step.detail }}
                                            <span v-if="step.phase"> · {{ phaseLabel(step.phase) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-shrink-0 gap-2">
                                        <SecondaryButton
                                            v-if="step.tab && !step.href && !step.action"
                                            type="button"
                                            size="sm"
                                            @click="activeTab = step.tab"
                                        >
                                            Open {{ step.tab }}
                                        </SecondaryButton>
                                        <PrimaryButton
                                            v-else-if="step.action || step.href"
                                            type="button"
                                            size="sm"
                                            @click="runStep(step)"
                                        >
                                            Continue
                                        </PrimaryButton>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div
                            v-if="deliveryProject.type === 'dmaic' && (deliveryProject.dmaic_profile?.business_problem || canViewDmaic)"
                            class="flex flex-col gap-3 rounded-card border border-warning/25 bg-warning-soft/40 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                        >
                            <div class="min-w-0">
                                <div class="text-xs font-semibold text-warning">Improvement context</div>
                                <p v-if="deliveryProject.dmaic_profile?.business_problem" class="mt-1 text-sm text-ink">
                                    Problem:
                                    <Link
                                        :href="route('business-problems.show', deliveryProject.dmaic_profile.business_problem.id)"
                                        class="app-link font-medium"
                                    >{{ deliveryProject.dmaic_profile.business_problem.title }}</Link>
                                </p>
                                <p v-else class="mt-1 text-sm text-ink-muted">No business problem linked yet — open Improve to start from a problem or KPI.</p>
                            </div>
                            <SecondaryButton type="button" size="sm" @click="activeTab = 'improve'">
                                Open Improve
                            </SecondaryButton>
                        </div>
                    </div>

                    <!-- Zone 2: At a glance -->
                    <div class="hub-zone">
                        <div class="hub-zone-label">At a glance</div>
                        <div class="hub-glance">
                            <div class="hub-glance-cell">
                                <span class="hub-glance-label">Health</span>
                                <StatusBadge :value="deliveryProject.health" />
                            </div>
                            <div class="hub-glance-cell">
                                <span class="hub-glance-label">Status</span>
                                <span class="hub-glance-value">{{ formatStatusLabel(deliveryProject.status) }}</span>
                            </div>
                            <div class="hub-glance-cell">
                                <span class="hub-glance-label">Owner</span>
                                <span class="hub-glance-value truncate">{{ deliveryProject.project_owner?.name || '—' }}</span>
                            </div>
                            <div class="hub-glance-cell">
                                <span class="hub-glance-label">Schedule</span>
                                <span class="hub-glance-value tabular-nums">
                                    {{ deliveryProject.start_date || '—' }}
                                    <span class="font-normal text-ink-muted">→</span>
                                    {{ deliveryProject.target_completion || '—' }}
                                </span>
                                <span class="app-meta">
                                    Rolled {{ deliveryProject.rolled_start_date || '—' }} → {{ deliveryProject.rolled_due_date || '—' }}
                                </span>
                                <span
                                    v-if="deliveryProject.target_completion && deliveryProject.rolled_due_date && deliveryProject.rolled_due_date > deliveryProject.target_completion"
                                    class="mt-0.5 text-xs font-medium text-warning"
                                >
                                    Past planned target
                                </span>
                            </div>
                            <div class="hub-glance-cell sm:col-span-2 lg:col-span-1">
                                <span class="hub-glance-label">Work scope</span>
                                <div class="flex flex-wrap gap-x-3 gap-y-1 text-sm">
                                    <span><span class="font-semibold text-ink">{{ hub.epics_count }}</span> <span class="text-ink-muted">epics</span></span>
                                    <span><span class="font-semibold text-ink">{{ hub.features_count }}</span> <span class="text-ink-muted">features</span></span>
                                    <span><span class="font-semibold text-ink">{{ hub.tasks_count }}</span> <span class="text-ink-muted">tasks</span></span>
                                </div>
                            </div>
                            <div v-if="nextExpectation" class="hub-glance-cell sm:col-span-2">
                                <span class="hub-glance-label">Next expectation</span>
                                <span class="hub-glance-value truncate">
                                    <Link
                                        v-if="nextExpectation.href"
                                        :href="nextExpectation.href"
                                        class="app-link"
                                    >{{ nextExpectation.title }}</Link>
                                    <template v-else>{{ nextExpectation.title }}</template>
                                    <span class="font-normal text-ink-muted"> · {{ nextExpectation.due_date || '—' }}</span>
                                </span>
                                <StatusBadge :value="nextExpectation.health" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Zone 3: Purpose -->
                    <div class="hub-zone">
                        <div class="hub-zone-label">Purpose</div>
                        <div class="rounded-card border border-border/70 bg-surface">
                            <div class="grid divide-y divide-border/60 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                                <div class="space-y-4 p-4 sm:p-5">
                                    <div>
                                        <div class="text-xs font-semibold text-ink-muted">Description</div>
                                        <p class="mt-1.5 text-[15px] leading-relaxed text-ink">
                                            {{ deliveryProject.description || 'No description yet.' }}
                                        </p>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-ink-muted">Success criteria</div>
                                        <p class="mt-1.5 text-sm leading-relaxed text-ink">
                                            {{ deliveryProject.success_criteria || '—' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="space-y-4 p-4 sm:p-5">
                                    <div>
                                        <div class="text-xs font-semibold text-ink-muted">Initiative</div>
                                        <p class="mt-1.5 text-sm">
                                            <Link
                                                v-if="deliveryProject.initiative"
                                                :href="route('initiatives.show', deliveryProject.initiative_id)"
                                                class="app-link font-medium"
                                            >{{ deliveryProject.initiative.name }}</Link>
                                            <span v-else class="text-ink-muted">—</span>
                                        </p>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-ink-muted">Systems</div>
                                        <div v-if="derivedSystems.length" class="mt-1.5 flex flex-wrap gap-2">
                                            <Link
                                                v-for="sys in derivedSystems"
                                                :key="sys.id"
                                                :href="route('systems.show', sys.id)"
                                                class="rounded-control bg-app px-2.5 py-1 text-sm font-medium text-ink transition duration-fast ease-out hover:bg-brand-soft hover:text-brand"
                                            >{{ sys.name }}</Link>
                                        </div>
                                        <p v-else class="mt-1.5 text-sm text-ink-muted">Link systems on the initiative.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Zone 4: Track -->
                    <div class="hub-zone">
                        <div class="flex flex-wrap items-end justify-between gap-2">
                            <div class="hub-zone-label !mb-0">Track</div>
                            <p class="text-sm text-ink-muted">Business outcomes and schedule checkpoints</p>
                        </div>
                        <div class="grid gap-4 lg:grid-cols-2 lg:gap-5">
                            <section class="hub-item-list">
                                <div class="flex items-center justify-between gap-2 border-b border-border/60 bg-app/40 px-4 py-3 sm:px-5">
                                    <div>
                                        <h3 class="app-section-title">KPI outcomes</h3>
                                        <p class="mt-0.5 text-xs text-ink-muted">Is delivery producing the result?</p>
                                    </div>
                                    <Link v-if="canManageKpis && createLinks.kpi" :href="createLinks.kpi">
                                        <SecondaryButton type="button" size="sm">Measure</SecondaryButton>
                                    </Link>
                                </div>
                                <template v-if="deliveryProject.kpis?.length">
                                    <div class="hub-item-cols grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_auto]">
                                        <span>Outcome</span>
                                        <span>Current / target</span>
                                        <span>Status</span>
                                    </div>
                                    <ul class="divide-y divide-border/60">
                                        <li
                                            v-for="kpi in deliveryProject.kpis"
                                            :key="kpi.id"
                                            class="hub-item-row sm:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_auto]"
                                        >
                                            <Link :href="route('kpis.show', kpi.id)" class="truncate font-semibold text-ink hover:text-brand">{{ kpi.name }}</Link>
                                            <div class="text-sm text-ink-muted">
                                                <span class="font-medium text-ink">{{ kpi.current_value ?? '—' }}</span>
                                                <span class="mx-1 text-slate-300">/</span>
                                                {{ kpi.target ?? '—' }}
                                            </div>
                                            <StatusBadge :value="kpi.status" />
                                        </li>
                                    </ul>
                                </template>
                                <EmptyState v-else title="No KPI outcomes linked" description="Link outcomes so delivery can be judged by business result." />
                            </section>

                            <section class="hub-item-list">
                                <div class="border-b border-border/60 bg-app/40 px-4 py-3 sm:px-5">
                                    <h3 class="app-section-title">Expectations</h3>
                                    <p class="mt-0.5 text-xs text-ink-muted">Checkpoints from Plan &amp; Ship — progress vs expected dates</p>
                                </div>

                                <div
                                    v-if="expectations?.length"
                                    class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-border/60 px-4 py-3 text-sm sm:px-5"
                                >
                                    <span v-if="nextExpectation" class="text-ink">
                                        Next:
                                        <Link
                                            v-if="nextExpectation.href"
                                            :href="nextExpectation.href"
                                            class="app-link font-medium"
                                        >{{ nextExpectation.title }}</Link>
                                        <span v-else class="font-medium">{{ nextExpectation.title }}</span>
                                        <span class="text-ink-muted"> · {{ nextExpectation.due_date || '—' }}</span>
                                    </span>
                                    <span class="text-ink-muted">
                                        <span class="font-medium text-ink">{{ movementCounts.on_track || 0 }}</span> on track
                                    </span>
                                    <span class="text-ink-muted">
                                        <span class="font-medium text-warning">{{ movementCounts.at_risk || 0 }}</span> at risk
                                    </span>
                                    <span class="text-ink-muted">
                                        <span class="font-medium text-danger">{{ movementCounts.slipped || 0 }}</span> slipped
                                    </span>
                                    <span v-if="expectationMovement?.target_completion" class="text-ink-muted">
                                        Project target
                                        <span class="font-medium tabular-nums text-ink">{{ expectationMovement.target_completion }}</span>
                                    </span>
                                </div>

                                <template v-if="expectations?.length">
                                    <div class="hub-item-cols hidden grid-cols-[minmax(0,1.4fr)_6rem_5rem_auto] sm:grid">
                                        <span>Checkpoint</span>
                                        <span>Expected</span>
                                        <span>Progress</span>
                                        <span>Health</span>
                                    </div>
                                    <ul class="divide-y divide-border/60">
                                        <li
                                            v-for="m in expectations"
                                            :key="m.id"
                                            class="hub-item-row gap-2 sm:grid-cols-[minmax(0,1.4fr)_6rem_5rem_auto]"
                                        >
                                            <div class="min-w-0">
                                                <Link
                                                    v-if="m.href"
                                                    :href="m.href"
                                                    class="truncate font-medium text-ink hover:text-brand"
                                                >{{ m.title }}</Link>
                                                <span v-else class="truncate font-medium text-ink">{{ m.title }}</span>
                                                <span class="mt-0.5 block text-xs text-ink-muted">{{ m.source_label }}</span>
                                            </div>
                                            <span class="text-sm text-ink-muted tabular-nums">{{ m.due_date || '—' }}</span>
                                            <div class="flex items-center gap-2">
                                                <div class="h-1.5 w-12 overflow-hidden rounded-full bg-slate-100">
                                                    <div
                                                        class="h-full rounded-full bg-brand"
                                                        :style="{ width: `${Math.min(100, m.progress_pct || 0)}%` }"
                                                    />
                                                </div>
                                                <span class="text-xs tabular-nums text-ink-muted">{{ m.progress_pct ?? 0 }}%</span>
                                            </div>
                                            <StatusBadge :value="m.health" />
                                        </li>
                                    </ul>
                                </template>
                                <EmptyState
                                    v-else
                                    title="No expectations yet"
                                    description="Add an epic with a due date on Plan, or a release target on Ship — checkpoints appear here."
                                />

                                <div
                                    v-if="canManage"
                                    class="border-t border-border/60 bg-app/30 px-4 py-3 sm:px-5"
                                >
                                    <button
                                        v-if="!showCustomExpectation"
                                        type="button"
                                        class="text-sm font-medium text-brand hover:underline"
                                        @click="showCustomExpectation = true"
                                    >
                                        Add custom expectation
                                    </button>
                                    <form
                                        v-else
                                        @submit.prevent="addMilestone"
                                        class="grid gap-3 sm:grid-cols-4"
                                    >
                                        <div class="sm:col-span-2">
                                            <InputLabel value="Title" />
                                            <TextInput v-model="milestoneForm.title" class="mt-1" required />
                                        </div>
                                        <div>
                                            <InputLabel value="Due" />
                                            <TextInput v-model="milestoneForm.due_date" type="date" class="mt-1" />
                                        </div>
                                        <div class="flex items-end gap-2">
                                            <PrimaryButton :disabled="milestoneForm.processing">Add</PrimaryButton>
                                            <SecondaryButton type="button" @click="showCustomExpectation = false">Cancel</SecondaryButton>
                                        </div>
                                    </form>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                <!-- Plan -->
                <div v-show="activeTab === 'plan'" class="hub-stage">
                    <header class="hub-stage-header">
                        <div>
                            <h2 class="hub-stage-title">Plan</h2>
                            <p class="hub-stage-desc">Assign features from initiative systems. Epics live on systems; this project schedules the work.</p>
                        </div>
                        <div class="hub-stage-actions">
                            <Link v-if="canManageEpics" :href="createLinks.epic">
                                <SecondaryButton type="button" size="sm">New epic on system</SecondaryButton>
                            </Link>
                            <Link v-if="canManageFeatures" :href="createLinks.feature">
                                <SecondaryButton type="button" size="sm">New feature</SecondaryButton>
                            </Link>
                        </div>
                    </header>

                    <section v-if="canManageFeatures" class="mb-4 rounded-md border border-border bg-app/40 p-4">
                        <div class="app-section-label">Assign from backlog</div>
                        <p class="mt-1 text-sm text-ink-muted">Features from initiative systems that are not yet on this project.</p>
                        <div class="mt-3 flex flex-wrap items-end gap-2">
                            <div class="min-w-[16rem] flex-1">
                                <SelectInput v-model="assignFeatureId" class="w-full">
                                    <option value="">Select feature…</option>
                                    <option v-for="f in assignableFeatures" :key="f.id" :value="f.id">
                                        {{ f.title }}{{ f.system ? ` (${f.system.name})` : '' }}
                                    </option>
                                </SelectInput>
                            </div>
                            <PrimaryButton type="button" size="sm" :disabled="!assignFeatureId" @click="assignFeature">Assign</PrimaryButton>
                        </div>
                        <p v-if="!assignableFeatures.length" class="mt-2 text-sm text-ink-muted">No backlog features available — create them on a linked system, or link systems on the initiative.</p>
                    </section>

                    <EmptyState
                        v-if="!planGroups.groups.length && !planGroups.unlinked.length"
                        title="Nothing planned yet"
                        description="Assign features from the system backlog, or create an epic/feature on a linked system."
                    >
                        <template v-if="canManageFeatures" #actions>
                            <SecondaryButton v-if="assignableFeatures.length" type="button" size="sm" @click="assignFeatureId = assignableFeatures[0]?.id || ''">
                                Pick from backlog
                            </SecondaryButton>
                            <Link v-if="canManageFeatures" :href="createLinks.feature">
                                <PrimaryButton type="button" size="sm">New feature</PrimaryButton>
                            </Link>
                        </template>
                    </EmptyState>

                    <div v-else class="space-y-4">
                        <section
                            v-for="group in planGroups.groups"
                            :key="group.epic.id"
                            class="hub-item-list"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border/60 bg-app/50 px-4 py-3 sm:px-5">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <span class="hub-step-label" title="Epic">E</span>
                                    <Link :href="route('epics.show', group.epic.id)" class="font-semibold text-ink hover:text-brand">
                                        {{ group.epic.title }}
                                    </Link>
                                    <StatusBadge :value="group.epic.status" />
                                    <span class="app-meta">{{ group.features.length }} feature{{ group.features.length === 1 ? '' : 's' }}</span>
                                </div>
                                <Link
                                    v-if="canManageFeatures"
                                    :href="route('features.create', { system_id: group.epic.system_id || group.epic.system?.id, epic_id: group.epic.id, delivery_project_id: deliveryProject.id, return_tab: 'plan' })"
                                    class="text-sm font-medium text-brand hover:underline"
                                >Add feature</Link>
                            </div>
                            <div v-if="group.features.length" class="hub-item-cols grid-cols-[minmax(0,1.4fr)_8rem_auto_auto] ps-4 sm:ps-12">
                                <span>Feature</span>
                                <span>DoD</span>
                                <span>Status</span>
                                <span class="text-end">Action</span>
                            </div>
                            <ul v-if="group.features.length" class="divide-y divide-border/60">
                                <li
                                    v-for="f in group.features"
                                    :key="f.id"
                                    class="hub-item-row sm:grid-cols-[minmax(0,1.4fr)_8rem_auto_auto] sm:ps-12"
                                >
                                    <Link :href="route('features.show', f.id)" class="truncate font-medium text-ink hover:text-brand">{{ f.title }}</Link>
                                    <div class="flex items-center gap-2">
                                        <div class="hub-dod" :title="`${dodProgress(f).done}/${dodProgress(f).total}`">
                                            <div class="hub-dod-fill" :style="{ width: `${dodProgress(f).pct}%` }" />
                                        </div>
                                        <span class="app-meta tabular-nums">{{ dodProgress(f).done }}/{{ dodProgress(f).total }}</span>
                                    </div>
                                    <StatusBadge :value="f.status" />
                                    <div class="flex flex-wrap justify-end gap-3 sm:text-end">
                                        <Link
                                            v-if="canManageTasks"
                                            :href="route('tasks.create', { delivery_project_id: deliveryProject.id, feature_id: f.id, return_tab: 'build' })"
                                            class="text-sm font-medium text-brand hover:underline"
                                        >Add task</Link>
                                        <button
                                            v-if="canManageFeatures"
                                            type="button"
                                            class="text-sm font-medium text-ink-muted hover:text-danger"
                                            @click="unassignFeature(f.id)"
                                        >Unassign</button>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="px-4 py-4 text-sm text-ink-muted sm:px-5 sm:ps-12">No features under this epic yet.</p>
                        </section>

                        <section v-if="planGroups.unlinked.length" class="hub-item-list border-dashed">
                            <div class="border-b border-border/60 px-4 py-3 sm:px-5">
                                <div class="app-section-label">Features without an epic</div>
                                <p class="mt-1 text-sm text-ink-muted">Link these to an epic when the theme is clear.</p>
                            </div>
                            <div class="hub-item-cols grid-cols-[minmax(0,1.4fr)_8rem_auto_auto]">
                                <span>Feature</span>
                                <span>DoD</span>
                                <span>Status</span>
                                <span class="text-end">Action</span>
                            </div>
                            <ul class="divide-y divide-border/60">
                                <li
                                    v-for="f in planGroups.unlinked"
                                    :key="f.id"
                                    class="hub-item-row sm:grid-cols-[minmax(0,1.4fr)_8rem_auto_auto]"
                                >
                                    <Link :href="route('features.show', f.id)" class="truncate font-medium text-ink hover:text-brand">{{ f.title }}</Link>
                                    <div class="flex items-center gap-2">
                                        <div class="hub-dod">
                                            <div class="hub-dod-fill" :style="{ width: `${dodProgress(f).pct}%` }" />
                                        </div>
                                        <span class="app-meta tabular-nums">{{ dodProgress(f).done }}/{{ dodProgress(f).total }}</span>
                                    </div>
                                    <StatusBadge :value="f.status" />
                                    <div class="sm:text-end">
                                        <Link
                                            v-if="canManageTasks"
                                            :href="route('tasks.create', { delivery_project_id: deliveryProject.id, feature_id: f.id, return_tab: 'build' })"
                                            class="text-sm font-medium text-brand hover:underline"
                                        >Add task</Link>
                                    </div>
                                </li>
                            </ul>
                        </section>
                    </div>
                </div>

                <!-- Build -->
                <div v-show="activeTab === 'build'" class="hub-stage">
                    <header class="hub-stage-header">
                        <div>
                            <h2 class="hub-stage-title">Build</h2>
                            <p class="hub-stage-desc">
                                Execute tasks. Optional sprints timebox the work —
                                <template v-if="hub.active_sprint">
                                    active:
                                    <Link :href="route('sprints.show', hub.active_sprint.id)" class="app-link">{{ hub.active_sprint.name }}</Link>
                                </template>
                                <template v-else>no active sprint.</template>
                            </p>
                        </div>
                        <div class="hub-stage-actions">
                            <div class="app-segment">
                                <button
                                    type="button"
                                    class="app-segment-btn"
                                    :class="buildView === 'list' ? 'app-segment-btn-active' : 'app-segment-btn-idle'"
                                    @click="buildView = 'list'"
                                >
                                    List
                                </button>
                                <button
                                    type="button"
                                    class="app-segment-btn"
                                    :class="buildView === 'board' ? 'app-segment-btn-active' : 'app-segment-btn-idle'"
                                    @click="buildView = 'board'"
                                >
                                    Board
                                </button>
                            </div>
                            <Link v-if="canManageTasks" :href="createLinks.task">
                                <PrimaryButton type="button" size="sm">Add task</PrimaryButton>
                            </Link>
                        </div>
                    </header>

                    <template v-if="buildView === 'board'">
                        <TaskKanban
                            v-if="board?.length"
                            :columns="board"
                            :show-assignee="true"
                            :wip-mode="'perPerson'"
                            :reload-only="['board', 'deliveryProject', 'hub']"
                            @updated="onBoardUpdated"
                        />
                        <EmptyState v-else title="No tasks yet" description="Create tasks from Plan features or add one here.">
                            <template v-if="canManageTasks" #actions>
                                <Link :href="createLinks.task">
                                    <PrimaryButton type="button" size="sm">Add task</PrimaryButton>
                                </Link>
                            </template>
                        </EmptyState>
                    </template>

                    <template v-else>
                        <div v-if="tasksByStatus.length" class="hub-item-list">
                            <div class="hub-item-cols grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,0.8fr)_7rem_auto]">
                                <span>Task</span>
                                <span>Feature</span>
                                <span>Assignee</span>
                                <span>Due</span>
                                <span>Priority</span>
                            </div>
                            <div
                                v-for="group in tasksByStatus"
                                :key="group.status"
                                class="border-b border-border/60 last:border-b-0"
                            >
                                <div class="app-group-header" :class="groupTone(group.status)">
                                    <span class="h-2 w-2 rounded-full" :class="groupDot(group.status)" aria-hidden="true" />
                                    <span>{{ group.label }}</span>
                                    <span class="font-normal text-ink-muted">{{ group.tasks.length }}</span>
                                </div>
                                <ul class="divide-y divide-border/60">
                                    <li
                                        v-for="task in group.tasks"
                                        :key="task.id"
                                        class="hub-item-row sm:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,0.8fr)_7rem_auto]"
                                    >
                                        <div class="min-w-0">
                                            <Link :href="route('tasks.show', task.id)" class="font-medium text-ink hover:text-brand">{{ task.title }}</Link>
                                            <div v-if="task.sprint" class="truncate app-meta">{{ task.sprint.name }}</div>
                                        </div>
                                        <div class="truncate text-sm text-ink-muted">
                                            <Link
                                                v-if="task.feature"
                                                :href="route('features.show', task.feature.id)"
                                                class="hover:text-brand"
                                            >{{ task.feature.title }}</Link>
                                            <span v-else class="italic">Unlinked</span>
                                        </div>
                                        <div class="truncate text-sm text-ink-muted">{{ task.assignee?.name || 'Unassigned' }}</div>
                                        <div class="text-sm text-ink-muted tabular-nums">{{ task.due_date || '—' }}</div>
                                        <StatusBadge v-if="task.priority" :value="task.priority" />
                                        <span v-else class="app-meta">—</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <EmptyState v-else title="No tasks yet" description="Create tasks from Plan features or add one here.">
                            <template v-if="canManageTasks" #actions>
                                <Link :href="createLinks.task">
                                    <PrimaryButton type="button" size="sm">Add task</PrimaryButton>
                                </Link>
                            </template>
                        </EmptyState>
                    </template>

                    <section class="rounded-card border border-border/60 bg-app/40 px-4 py-4 sm:px-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 class="app-section-title">Sprints</h3>
                                <p class="mt-0.5 text-sm text-ink-muted">Optional timeboxes — tasks above are the source of truth.</p>
                            </div>
                            <Link v-if="canManageSprints" :href="createLinks.sprint">
                                <SecondaryButton type="button" size="sm">Add sprint</SecondaryButton>
                            </Link>
                        </div>
                        <div v-if="deliveryProject.sprints?.length" class="mt-3 overflow-hidden rounded-control border border-border/60 bg-surface">
                            <div class="hub-item-cols grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto]">
                                <span>Sprint</span>
                                <span>Window</span>
                                <span>Status</span>
                            </div>
                            <ul class="divide-y divide-border/60">
                                <li
                                    v-for="s in deliveryProject.sprints"
                                    :key="s.id"
                                    class="hub-item-row sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto]"
                                >
                                    <Link :href="route('sprints.show', s.id)" class="truncate font-medium text-ink hover:text-brand">{{ s.name }}</Link>
                                    <span class="text-sm text-ink-muted tabular-nums">{{ s.start_date || '—' }} → {{ s.end_date || '—' }}</span>
                                    <StatusBadge :value="s.status" />
                                </li>
                            </ul>
                        </div>
                        <p v-else class="mt-3 text-sm text-ink-muted">No sprints yet.</p>
                    </section>
                </div>

                <!-- Schedule -->
                <div v-show="activeTab === 'schedule'" class="hub-stage">
                    <header class="hub-stage-header">
                        <div>
                            <h2 class="hub-stage-title">Schedule</h2>
                            <p class="hub-stage-desc">
                                Timeline for this project.
                                Slip {{ schedule?.conflicts?.slip_count || 0 }} · Overlaps {{ schedule?.conflicts?.overlap_count || 0 }}
                                ·
                                <Link :href="route('schedule.index', { project_id: deliveryProject.id })" class="app-link">Portfolio schedule</Link>
                            </p>
                        </div>
                        <div class="hub-stage-actions">
                            <SelectInput v-model="scheduleView" class="w-auto">
                                <option value="Week">Week</option>
                                <option value="Month">Month</option>
                                <option value="Year">Year</option>
                            </SelectInput>
                        </div>
                    </header>
                    <div class="overflow-hidden rounded-card border border-border/70">
                        <div class="p-2 sm:p-3">
                            <ScheduleGantt :bars="schedule?.bars || []" :view-mode="scheduleView" />
                        </div>
                    </div>
                    <SectionCard variant="plain" title="Undated work" flush>
                        <div v-if="(schedule?.undated || []).length" class="hub-item-list !rounded-none !border-0 !border-t border-border/60">
                            <div class="hub-item-cols grid-cols-[5rem_minmax(0,1fr)_auto]">
                                <span>Type</span>
                                <span>Item</span>
                                <span>Status</span>
                            </div>
                            <ul class="divide-y divide-border/60">
                                <li
                                    v-for="item in schedule.undated"
                                    :key="item.id"
                                    class="hub-item-row sm:grid-cols-[5rem_minmax(0,1fr)_auto]"
                                >
                                    <span class="hub-type-chip">{{ item.type }}</span>
                                    <Link :href="item.href" class="truncate font-medium text-ink hover:text-brand">{{ item.title }}</Link>
                                    <StatusBadge v-if="item.status" :value="item.status" />
                                    <span v-else class="app-meta">—</span>
                                </li>
                            </ul>
                        </div>
                        <EmptyState v-else title="All items have dates" description="Or none exist in the tree yet." />
                    </SectionCard>
                </div>

                <!-- Quality -->
                <div v-show="activeTab === 'quality'" class="hub-stage">
                    <header class="hub-stage-header">
                        <div>
                            <h2 class="hub-stage-title">Quality</h2>
                            <p class="hub-stage-desc">Track defects and support load against this project. Critical items sort first.</p>
                        </div>
                        <div class="hub-stage-actions">
                            <Link v-if="canViewBugs" :href="createLinks.bug">
                                <SecondaryButton type="button" size="sm">Report bug</SecondaryButton>
                            </Link>
                            <Link v-if="canViewSupport" :href="createLinks.support">
                                <SecondaryButton type="button" size="sm">New ticket</SecondaryButton>
                            </Link>
                        </div>
                    </header>
                    <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                        <SectionCard variant="plain" :title="`Bugs · ${openBugs.length} open`" flush>
                            <div v-if="sortedOpenBugs.length" class="hub-item-list !rounded-none !border-0 !border-t border-border/60">
                                <div class="hub-item-cols grid-cols-[minmax(0,1fr)_auto_minmax(0,0.7fr)_auto]">
                                    <span>Bug</span>
                                    <span>Severity</span>
                                    <span>Assignee</span>
                                    <span>Status</span>
                                </div>
                                <ul class="divide-y divide-border/60">
                                    <li
                                        v-for="b in sortedOpenBugs"
                                        :key="b.id"
                                        class="hub-item-row sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,0.7fr)_auto]"
                                    >
                                        <Link :href="route('bugs.show', b.id)" class="truncate font-medium text-ink hover:text-brand">{{ b.title }}</Link>
                                        <StatusBadge v-if="b.severity" :value="b.severity" />
                                        <span v-else class="app-meta">—</span>
                                        <span class="truncate text-sm text-ink-muted">{{ b.assignee?.name || 'Unassigned' }}</span>
                                        <StatusBadge :value="b.status" />
                                    </li>
                                </ul>
                            </div>
                            <EmptyState v-else title="No open bugs" description="Report defects against this project." />
                        </SectionCard>
                        <SectionCard variant="plain" :title="`Support · ${openSupport.length} open`" flush>
                            <div v-if="sortedOpenSupport.length" class="hub-item-list !rounded-none !border-0 !border-t border-border/60">
                                <div class="hub-item-cols grid-cols-[minmax(0,1fr)_auto_minmax(0,0.7fr)_auto]">
                                    <span>Ticket</span>
                                    <span>Priority</span>
                                    <span>Assignee</span>
                                    <span>Status</span>
                                </div>
                                <ul class="divide-y divide-border/60">
                                    <li
                                        v-for="t in sortedOpenSupport"
                                        :key="t.id"
                                        class="hub-item-row sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,0.7fr)_auto]"
                                    >
                                        <Link :href="route('support.show', t.id)" class="truncate font-medium text-ink hover:text-brand">{{ t.title }}</Link>
                                        <StatusBadge v-if="t.priority" :value="t.priority" />
                                        <span v-else class="app-meta">—</span>
                                        <span class="truncate text-sm text-ink-muted">{{ t.assignee?.name || 'Unassigned' }}</span>
                                        <StatusBadge :value="t.status" />
                                    </li>
                                </ul>
                            </div>
                            <EmptyState v-else title="No open support tickets" description="Log customer or ops requests here." />
                        </SectionCard>
                    </div>
                </div>

                <!-- Ship -->
                <div v-show="activeTab === 'ship'" class="hub-stage">
                    <header class="hub-stage-header">
                        <div>
                            <h2 class="hub-stage-title">Ship</h2>
                            <p class="hub-stage-desc">Package verified work: pull ready tasks into a release, then track deployment.</p>
                        </div>
                        <div class="hub-stage-actions">
                            <Link v-if="canManageReleases" :href="createLinks.release">
                                <PrimaryButton type="button" size="sm">Add release</PrimaryButton>
                            </Link>
                        </div>
                    </header>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <section class="hub-step overflow-hidden">
                            <div class="border-b border-border/60 px-4 py-4 sm:px-5">
                                <div class="flex items-center gap-2">
                                    <span class="hub-step-label">1</span>
                                    <h3 class="app-section-title">Ready to ship</h3>
                                    <span class="app-meta">{{ readyTasks.length }}</span>
                                </div>
                                <p class="mt-1 text-sm text-ink-muted">Tasks in ready for release after QA.</p>
                            </div>
                            <div v-if="readyTasks.length">
                                <div class="hub-item-cols grid-cols-[minmax(0,1fr)_minmax(0,0.8fr)_auto]">
                                    <span>Task</span>
                                    <span>Feature</span>
                                    <span>Assignee</span>
                                </div>
                                <ul class="divide-y divide-border/60">
                                    <li
                                        v-for="t in readyTasks"
                                        :key="t.id"
                                        class="hub-item-row sm:grid-cols-[minmax(0,1fr)_minmax(0,0.8fr)_auto]"
                                    >
                                        <Link :href="route('tasks.show', t.id)" class="truncate font-medium text-ink hover:text-brand">{{ t.title }}</Link>
                                        <span class="truncate text-sm text-ink-muted">{{ t.feature?.title || '—' }}</span>
                                        <span class="truncate text-sm text-ink-muted">{{ t.assignee?.name || '—' }}</span>
                                    </li>
                                </ul>
                            </div>
                            <p v-else class="px-4 py-4 text-sm text-ink-muted sm:px-5">Nothing ready yet — finish QA on Build tasks first.</p>
                        </section>
                        <section class="hub-step overflow-hidden">
                            <div class="border-b border-border/60 px-4 py-4 sm:px-5">
                                <div class="flex items-center gap-2">
                                    <span class="hub-step-label">2</span>
                                    <h3 class="app-section-title">Releases</h3>
                                </div>
                                <p class="mt-1 text-sm text-ink-muted">Named packages of ready work for deployment.</p>
                            </div>
                            <div v-if="deliveryProject.releases?.length">
                                <div class="hub-item-cols grid-cols-[minmax(0,1fr)_5rem_7rem_auto]">
                                    <span>Release</span>
                                    <span>Version</span>
                                    <span>Target</span>
                                    <span>Status</span>
                                </div>
                                <ul class="divide-y divide-border/60">
                                    <li
                                        v-for="r in deliveryProject.releases"
                                        :key="r.id"
                                        class="hub-item-row sm:grid-cols-[minmax(0,1fr)_5rem_7rem_auto]"
                                    >
                                        <Link :href="route('releases.show', r.id)" class="truncate font-medium text-ink hover:text-brand">{{ r.name }}</Link>
                                        <span class="text-sm text-ink-muted">{{ r.version || '—' }}</span>
                                        <span class="text-sm text-ink-muted tabular-nums">{{ r.target_date || '—' }}</span>
                                        <StatusBadge :value="r.status" />
                                    </li>
                                </ul>
                            </div>
                            <div v-else class="px-4 py-4 sm:px-5">
                                <p class="text-sm text-ink-muted">No releases yet.</p>
                                <Link v-if="canManageReleases" :href="createLinks.release" class="mt-2 inline-block">
                                    <SecondaryButton type="button" size="sm">Add release</SecondaryButton>
                                </Link>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Improve -->
                <div v-show="activeTab === 'improve'" class="hub-stage">
                    <header class="hub-stage-header">
                        <div>
                            <h2 class="hub-stage-title">Improve</h2>
                            <p class="hub-stage-desc">Run DMAIC on this project. Fixes still ship through Plan → Build → Ship.</p>
                        </div>
                        <div class="hub-stage-actions">
                            <Link v-if="canManageProblems" :href="createLinks.problem">
                                <SecondaryButton type="button" size="sm">Add business problem</SecondaryButton>
                            </Link>
                        </div>
                    </header>

                    <div class="rounded-card border border-warning/25 bg-warning-soft/60 p-4 sm:p-5">
                        <div class="app-section-label text-warning">Starts from</div>
                        <div class="mt-2 grid gap-3 text-sm sm:grid-cols-3">
                            <div>
                                <div class="text-ink-muted">Initiative</div>
                                <Link
                                    v-if="deliveryProject.initiative"
                                    :href="route('initiatives.show', deliveryProject.initiative_id)"
                                    class="app-link font-medium"
                                >{{ deliveryProject.initiative.name }}</Link>
                                <span v-else>—</span>
                            </div>
                            <div>
                                <div class="text-ink-muted">Business problem</div>
                                <Link
                                    v-if="deliveryProject.dmaic_profile?.business_problem"
                                    :href="route('business-problems.show', deliveryProject.dmaic_profile.business_problem.id)"
                                    class="app-link font-medium"
                                >{{ deliveryProject.dmaic_profile.business_problem.title }}</Link>
                                <span v-else class="text-ink-muted">Not linked yet</span>
                            </div>
                            <div>
                                <div class="text-ink-muted">Outcome KPI</div>
                                <template v-if="deliveryProject.kpis?.length">
                                    <Link
                                        v-for="kpi in deliveryProject.kpis"
                                        :key="kpi.id"
                                        :href="route('kpis.show', kpi.id)"
                                        class="app-link me-2 font-medium"
                                    >{{ kpi.name }}</Link>
                                </template>
                                <span v-else class="text-ink-muted">Link a KPI on Overview</span>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-warning">
                            After you register a fix, continue on
                            <button type="button" class="font-semibold text-brand underline" @click="activeTab = 'plan'">Plan</button>
                            →
                            <button type="button" class="font-semibold text-brand underline" @click="activeTab = 'build'">Build</button>
                            →
                            <button type="button" class="font-semibold text-brand underline" @click="activeTab = 'ship'">Ship</button>.
                        </p>
                    </div>

                    <div v-if="deliveryProject.dmaic_profile">
                        <ImprovementWorkspace
                            :profile="deliveryProject.dmaic_profile"
                            :can-manage="canManageDmaic"
                            :can-approve-gate="canApproveGate"
                            :features="improvementFeatures"
                        />
                    </div>
                    <SectionCard v-else title="Start improvement cycle">
                        <p class="text-sm text-ink-muted">
                            Initialize staged work: Frame the problem → Set the baseline → Find root causes → Implement fixes → Sustain results.
                        </p>
                        <PrimaryButton v-if="canManageDmaic" class="mt-4" type="button" @click="bootstrapDmaic">
                            Start improvement workspace
                        </PrimaryButton>
                    </SectionCard>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

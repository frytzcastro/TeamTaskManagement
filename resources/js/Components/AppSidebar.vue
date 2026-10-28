<script setup>
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SidebarIcon from '@/Components/SidebarIcon.vue';
import SidebarNavLink from '@/Components/SidebarNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    /** When true, render as mobile drawer content (no fixed width chrome). */
    mobile: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const permissions = computed(() => page.props.auth.user?.permissions ?? []);
const roles = computed(() => page.props.auth.user?.roles ?? []);
const can = (permission) => permissions.value.includes(permission);
const hasRole = (...names) => names.some((n) => roles.value.includes(n));

const isImprovementLead = computed(() => hasRole('improvement_lead') && !hasRole('admin', 'executive', 'project_manager'));
const isSupportOnly = computed(() => hasRole('support') && !hasRole('admin', 'executive', 'project_manager', 'product_owner', 'improvement_lead'));
const isDevLike = computed(() => (hasRole('developer', 'qa') || can('dashboards.developer')) && !hasRole('admin', 'executive', 'project_manager', 'product_owner', 'improvement_lead'));
const isPm = computed(() => hasRole('project_manager') && !hasRole('admin', 'executive'));
const isStrategyLead = computed(() => hasRole('executive', 'product_owner', 'admin') || (can('dashboards.executive') && !isImprovementLead.value && !isPm.value && !isDevLike.value && !isSupportOnly.value));

const showMoreWork = computed(() =>
    can('systems.view') || can('epics.view') || can('features.view') || can('tasks.view')
    || can('sprints.view') || can('releases.view') || can('dmaic.view'),
);
const showMoreQuality = computed(() =>
    (can('bugs.view') && !isDevLike.value && !isPm.value)
    || (can('support.view') && !isSupportOnly.value),
);
const showMoreStrategy = computed(() =>
    (can('pillars.view') && !isStrategyLead.value)
    || (can('initiatives.view') && !isStrategyLead.value)
    || (can('business_problems.view') && !isImprovementLead.value)
    || (can('kpis.view') && !isImprovementLead.value)
    || (can('dashboards.dmaic') && !isImprovementLead.value)
    || (can('dashboards.team') && !isPm.value && !isStrategyLead.value),
);
const showMore = computed(() => showMoreWork.value || showMoreQuality.value || showMoreStrategy.value);

const moreActive = computed(() =>
    route().current('epics.*') || route().current('features.*') || route().current('tasks.*')
    || route().current('sprints.*') || route().current('releases.*') || route().current('systems.*')
    || route().current('dmaic.*') || route().current('portfolio.*') || route().current('pillars.*')
    || route().current('initiatives.*') || route().current('business-problems.*') || route().current('kpis.*')
    || route().current('bugs.*') || route().current('support.*'),
);

const moreOpen = ref(moreActive.value);
const myProjects = computed(() => page.props.nav?.my_projects ?? []);
const isProjectActive = (id) => String(page.url || '').includes(`/delivery-projects/${id}`);

const projectMarker = (id) => {
    const palette = ['#1840c0', '#38c890', '#f8c830', '#3878f0', '#dc2626', '#0d9488', '#7c3aed'];
    let hash = 0;
    const s = String(id || '');
    for (let i = 0; i < s.length; i += 1) hash = (hash + s.charCodeAt(i) * (i + 1)) % palette.length;
    return palette[hash];
};

const userName = computed(() => page.props.auth?.user?.name || 'Profile');
const userInitials = computed(() => {
    const parts = String(userName.value).trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});

const sectionLabel = 'flex items-center justify-between px-2.5 pb-1.5 pt-5 text-[10px] font-semibold uppercase tracking-[0.1em] text-ink-muted';
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-border/70 px-4">
            <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-2.5 text-ink">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center overflow-visible">
                    <ApplicationLogo size="sm" />
                </span>
                <span class="truncate text-sm font-semibold tracking-tight">MyTask</span>
            </Link>
        </div>

        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
            <div :class="sectionLabel" class="!pt-0">
                <span>Main</span>
            </div>

            <template v-if="isDevLike">
                <SidebarNavLink v-if="can('dashboards.developer')" icon="work" :href="route('dashboards.developer')" :active="route().current('dashboards.developer')">My Work</SidebarNavLink>
                <SidebarNavLink v-if="can('delivery_projects.view')" icon="projects" :href="route('delivery-projects.index')" :active="route().current('delivery-projects.*') && !route().current('delivery-projects.show')">Projects</SidebarNavLink>
                <SidebarNavLink v-if="can('bugs.view')" icon="bugs" :href="route('bugs.index')" :active="route().current('bugs.*')">Bugs</SidebarNavLink>
            </template>

            <template v-else-if="isSupportOnly">
                <SidebarNavLink v-if="can('support.view')" icon="support" :href="route('support.index')" :active="route().current('support.*')">Support</SidebarNavLink>
                <SidebarNavLink v-if="can('delivery_projects.view')" icon="projects" :href="route('delivery-projects.index')" :active="route().current('delivery-projects.*') && !route().current('delivery-projects.show')">Projects</SidebarNavLink>
            </template>

            <template v-else-if="isImprovementLead">
                <SidebarNavLink v-if="can('dashboards.dmaic')" icon="improve" :href="route('dashboards.dmaic')" :active="route().current('dashboards.dmaic')">Improve</SidebarNavLink>
                <SidebarNavLink v-if="can('delivery_projects.view')" icon="projects" :href="route('delivery-projects.index')" :active="route().current('delivery-projects.*') && !route().current('delivery-projects.show')">Projects</SidebarNavLink>
                <SidebarNavLink v-if="can('dashboards.team') || can('dashboards.executive')" icon="schedule" :href="route('schedule.index')" :active="route().current('schedule.*')">Schedule</SidebarNavLink>
                <SidebarNavLink v-if="can('business_problems.view')" icon="problems" :href="route('business-problems.index')" :active="route().current('business-problems.*')">Problems</SidebarNavLink>
                <SidebarNavLink v-if="can('kpis.view')" icon="kpi" :href="route('kpis.index')" :active="route().current('kpis.*')">KPI Outcomes</SidebarNavLink>
            </template>

            <template v-else-if="isPm">
                <SidebarNavLink v-if="can('dashboards.team')" icon="team" :href="route('dashboards.team')" :active="route().current('dashboards.team')">Team</SidebarNavLink>
                <SidebarNavLink v-if="can('delivery_projects.view')" icon="projects" :href="route('delivery-projects.index')" :active="route().current('delivery-projects.*') && !route().current('delivery-projects.show')">Projects</SidebarNavLink>
                <SidebarNavLink v-if="can('dashboards.team') || can('dashboards.executive')" icon="schedule" :href="route('schedule.index')" :active="route().current('schedule.*')">Schedule</SidebarNavLink>
                <SidebarNavLink v-if="can('bugs.view') || can('support.view')" icon="quality" :href="can('bugs.view') ? route('bugs.index') : route('support.index')" :active="route().current('bugs.*') || route().current('support.*')">Quality</SidebarNavLink>
            </template>

            <template v-else>
                <SidebarNavLink v-if="can('pillars.view')" icon="portfolio" :href="route('portfolio.index')" :active="route().current('portfolio.*')">Portfolio</SidebarNavLink>
                <SidebarNavLink v-if="can('delivery_projects.view')" icon="projects" :href="route('delivery-projects.index')" :active="route().current('delivery-projects.*') && !route().current('delivery-projects.show')">Projects</SidebarNavLink>
                <SidebarNavLink v-if="can('dashboards.team') || can('dashboards.executive')" icon="schedule" :href="route('schedule.index')" :active="route().current('schedule.*')">Schedule</SidebarNavLink>
                <template v-if="can('dashboards.executive') || can('dashboards.team') || can('dashboards.dmaic')">
                    <div :class="sectionLabel"><span>Dashboards</span></div>
                    <SidebarNavLink v-if="can('dashboards.executive')" nested icon="dashboard" :href="route('dashboards.executive')" :active="route().current('dashboards.executive')">Executive</SidebarNavLink>
                    <SidebarNavLink v-if="can('dashboards.team')" nested icon="team" :href="route('dashboards.team')" :active="route().current('dashboards.team')">Team Delivery</SidebarNavLink>
                    <SidebarNavLink v-if="can('dashboards.dmaic')" nested icon="improve" :href="route('dashboards.dmaic')" :active="route().current('dashboards.dmaic')">Improvement overview</SidebarNavLink>
                </template>
            </template>

            <template v-if="can('delivery_projects.view') && myProjects.length">
                <div :class="sectionLabel">
                    <span>My projects</span>
                </div>
                <div class="space-y-0.5 ps-1">
                    <SidebarNavLink
                        v-for="project in myProjects"
                        :key="project.id"
                        nested
                        :marker="projectMarker(project.id)"
                        :href="route('delivery-projects.show', project.id)"
                        :active="isProjectActive(project.id)"
                    >
                        {{ project.name }}
                    </SidebarNavLink>
                </div>
            </template>

            <div v-if="showMore" class="pt-1">
                <button
                    type="button"
                    class="group relative flex min-h-10 w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-sm font-medium text-ink-muted transition duration-fast ease-out hover:bg-white hover:text-ink hover:shadow-soft"
                    :class="moreActive ? 'text-ink' : ''"
                    @click="moreOpen = !moreOpen"
                >
                    <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center text-ink-muted group-hover:text-ink">
                        <SidebarIcon name="more" />
                    </span>
                    <span class="min-w-0 flex-1 truncate text-start">More</span>
                    <svg class="h-4 w-4 shrink-0 text-ink-muted transition duration-surface ease-out" :class="moreOpen ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
                <div v-show="moreOpen" class="mt-0.5 space-y-0.5">
                    <template v-if="showMoreWork">
                        <div :class="sectionLabel" class="!pt-2"><span>Work items</span></div>
                        <SidebarNavLink v-if="can('systems.view')" nested :href="route('systems.index')" :active="route().current('systems.*')">Systems</SidebarNavLink>
                        <SidebarNavLink v-if="can('epics.view')" nested :href="route('epics.index')" :active="route().current('epics.*')">Epics</SidebarNavLink>
                        <SidebarNavLink v-if="can('features.view')" nested :href="route('features.index')" :active="route().current('features.*')">Features</SidebarNavLink>
                        <SidebarNavLink v-if="can('tasks.view')" nested :href="route('tasks.index')" :active="route().current('tasks.*')">Tasks</SidebarNavLink>
                        <SidebarNavLink v-if="can('sprints.view')" nested :href="route('sprints.index')" :active="route().current('sprints.*')">Sprints</SidebarNavLink>
                        <SidebarNavLink v-if="can('releases.view')" nested :href="route('releases.index')" :active="route().current('releases.*')">Releases</SidebarNavLink>
                        <SidebarNavLink v-if="can('dmaic.view')" nested :href="route('dmaic.index')" :active="route().current('dmaic.*')">Improvement cycles</SidebarNavLink>
                    </template>
                    <template v-if="showMoreQuality">
                        <div :class="sectionLabel" class="!pt-2"><span>Quality</span></div>
                        <SidebarNavLink v-if="can('bugs.view') && !isDevLike && !isPm" nested :href="route('bugs.index')" :active="route().current('bugs.*')">Bugs</SidebarNavLink>
                        <SidebarNavLink v-if="can('support.view') && !isSupportOnly" nested :href="route('support.index')" :active="route().current('support.*')">Support</SidebarNavLink>
                    </template>
                    <template v-if="showMoreStrategy">
                        <div :class="sectionLabel" class="!pt-2"><span>Strategy</span></div>
                        <SidebarNavLink v-if="can('pillars.view') && !isStrategyLead" nested :href="route('portfolio.index')" :active="route().current('portfolio.*')">Portfolio</SidebarNavLink>
                        <SidebarNavLink v-if="can('pillars.view')" nested :href="route('pillars.index')" :active="route().current('pillars.*')">Pillars</SidebarNavLink>
                        <SidebarNavLink v-if="can('initiatives.view')" nested :href="route('initiatives.index')" :active="route().current('initiatives.*')">Initiatives</SidebarNavLink>
                        <SidebarNavLink v-if="can('business_problems.view') && !isImprovementLead" nested :href="route('business-problems.index')" :active="route().current('business-problems.*')">Problems</SidebarNavLink>
                        <SidebarNavLink v-if="can('kpis.view') && !isImprovementLead" nested :href="route('kpis.index')" :active="route().current('kpis.*')">KPI Outcomes</SidebarNavLink>
                        <SidebarNavLink v-if="can('dashboards.dmaic') && !isImprovementLead" nested :href="route('dashboards.dmaic')" :active="route().current('dashboards.dmaic')">Improvement overview</SidebarNavLink>
                        <SidebarNavLink v-if="can('dashboards.team') && !isPm && !isStrategyLead" nested :href="route('dashboards.team')" :active="route().current('dashboards.team')">Team Delivery</SidebarNavLink>
                    </template>
                </div>
            </div>

            <div v-if="can('users.manage')" class="pt-1">
                <SidebarNavLink
                    icon="admin"
                    :href="route('admin.users.index')"
                    :active="route().current('admin.users.*')"
                >
                    Admin
                </SidebarNavLink>
            </div>
        </nav>

        <div class="mt-auto shrink-0 border-t border-border/70 px-3 py-3">
            <Link
                :href="route('profile.edit')"
                class="group relative flex min-h-11 items-center gap-2.5 rounded-lg px-2.5 py-2 transition duration-fast ease-out"
                :class="route().current('profile.*') ? 'bg-brand-soft shadow-soft' : 'hover:bg-white hover:shadow-soft'"
            >
                <span
                    v-if="route().current('profile.*')"
                    class="absolute inset-y-1.5 start-0 w-1 rounded-full bg-brand"
                    aria-hidden="true"
                />
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-soft text-[11px] font-semibold text-brand ring-2 ring-white">
                    {{ userInitials }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-ink">{{ userName }}</span>
                    <span class="block text-xs text-ink-muted">Profile</span>
                </span>
                <span class="text-ink-muted group-hover:text-ink">
                    <SidebarIcon name="settings" />
                </span>
            </Link>
        </div>
    </div>
</template>

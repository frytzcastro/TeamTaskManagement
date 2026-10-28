/** Shared status → badge class map and chart hex tokens. */

export const CHART_COLORS = {
    brand: '#1840c0',
    success: '#38c890',
    warning: '#f8c830',
    danger: '#dc2626',
    info: '#3878f0',
    muted: '#6b7280',
    overlap: '#3878f0',
};

const tone = {
    green: 'bg-success-soft text-success',
    amber: 'bg-warning-soft text-warning',
    red: 'bg-danger-soft text-danger',
    blue: 'bg-info-soft text-info',
    brand: 'bg-brand-soft text-brand',
    gray: 'bg-slate-100 text-slate-600',
};

/** @type {Record<string, string>} */
export const STATUS_BADGE_CLASSES = {
    // Health / generic tones
    green: tone.green,
    amber: tone.amber,
    red: tone.red,
    blue: tone.blue,
    no_data: tone.gray,

    // Shared workflow
    backlog: tone.gray,
    ready: tone.brand,
    in_progress: tone.blue,
    blocked: tone.red,
    done: tone.green,
    completed: tone.green,
    active: tone.blue,
    inactive: tone.gray,
    draft: tone.gray,
    cancelled: tone.gray,
    canceled: tone.gray,
    pending: tone.amber,
    open: tone.brand,
    closed: tone.gray,
    resolved: tone.green,
    waiting: tone.amber,
    converted: tone.brand,
    paused: tone.amber,
    on_hold: tone.amber,
    delayed: tone.amber,
    at_risk: tone.amber,
    slipped: tone.red,

    // Tasks
    code_review: tone.brand,
    qa: tone.brand,
    qa_failed: tone.red,
    ready_for_release: tone.green,
    released: tone.green,

    // Bugs
    reported: tone.amber,
    triaged: tone.brand,
    assigned: tone.blue,
    in_development: tone.blue,
    ready_for_qa: tone.brand,
    qa_testing: tone.brand,
    reopened: tone.red,

    // Severity / priority
    critical: tone.red,
    high: tone.amber,
    medium: tone.brand,
    low: tone.gray,

    // Releases / deployments / sprints
    planned: tone.gray,
    in_qa: tone.brand,
    approved: tone.green,
    deployed: tone.green,
    rolled_back: tone.red,
    success: tone.green,
    failed: tone.red,

    // DMAIC / improvement
    define: tone.brand,
    measure: tone.brand,
    analyze: tone.amber,
    improve: tone.blue,
    control: tone.green,
    suspected: tone.amber,
    validated: tone.green,
    rejected: tone.red,
    approved_gate: tone.green,
    pending_gate: tone.amber,

    // KPI / systems / pillars
    on_track: tone.green,
    off_track: tone.red,
    not_started: tone.gray,
    measuring: tone.blue,
};

export function statusBadgeClass(value) {
    if (!value) return tone.gray;
    const key = String(value).toLowerCase();
    return STATUS_BADGE_CLASSES[key] || tone.gray;
}

export function formatStatusLabel(value) {
    if (!value) return '—';
    return String(value).replaceAll('_', ' ');
}

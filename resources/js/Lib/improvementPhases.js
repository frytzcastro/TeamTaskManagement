/** User-facing labels for improvement-cycle phases (DMAIC under the hood). */
export const PHASE_LABELS = {
    DEFINE: 'Frame the problem',
    MEASURE: 'Set the baseline',
    ANALYZE: 'Find root causes',
    IMPROVE: 'Implement fixes',
    CONTROL: 'Sustain results',
    CLOSED: 'Closed',
};

export function phaseLabel(phase) {
    if (!phase) return '—';
    return PHASE_LABELS[phase] || String(phase).replaceAll('_', ' ');
}

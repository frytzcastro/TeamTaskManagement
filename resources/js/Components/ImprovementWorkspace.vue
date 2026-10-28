<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import SectionCard from '@/Components/SectionCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { phaseLabel } from '@/Lib/improvementPhases';

const props = defineProps({
    profile: Object,
    canManage: Boolean,
    canApproveGate: Boolean,
    features: { type: Array, default: () => [] },
});

const page = usePage();

const currentPhase = computed(() => props.profile?.phases?.find((p) => p.phase === props.profile.current_phase));
const currentGate = computed(() => currentPhase.value?.gates?.[0] || currentPhase.value?.gate);

const toggleDeliverable = (d) => {
    useForm({ completed: !d.completed, evidence_url: d.evidence_url || '' }).put(route('dmaic.deliverables.update', d.id), { preserveScroll: true });
};

const measureForm = useForm({ metric_name: '', baseline_value: '', definition: '', data_source: '', data_quality_status: 'ok' });
const rootForm = useForm({ title: '', method: '5 Whys', status: 'suspected', evidence_title: '', evidence_body: '' });
const improveForm = useForm({ title: '', dmaic_root_cause_id: '', feature_id: '', status: 'planned', create_feature: true, before_measurement: '', expected_measurement: '', after_measurement: '' });
const controlForm = useForm({ title: 'Control plan', metric_name: '', kpi_id: '', baseline: '', target: '', current_value: '', ucl: '', lcl: '' });
const gateForm = useForm({ comments: '' });
const rejectForm = useForm({ rejection_reason: '' });

const approveGate = () => gateForm.post(route('dmaic.gates.approve', currentGate.value.id), { preserveScroll: true });
const rejectGate = () => rejectForm.post(route('dmaic.gates.reject', currentGate.value.id), { preserveScroll: true });
const saveMeasure = () => measureForm.post(route('dmaic.measurement-plans.store', props.profile.id), { onSuccess: () => measureForm.reset() });
const saveRoot = () => rootForm.post(route('dmaic.root-causes.store', props.profile.id), { onSuccess: () => rootForm.reset() });
const saveImprove = () => improveForm.post(route('dmaic.improvements.store', props.profile.id), { onSuccess: () => improveForm.reset() });
const saveControl = () => controlForm.post(route('dmaic.control-plans.store', props.profile.id), { onSuccess: () => controlForm.reset() });
</script>

<template>
    <div v-if="profile" class="space-y-6">
        <div class="flex flex-wrap gap-1.5" role="list" aria-label="DMAIC stages">
            <span
                v-for="ph in profile.phases"
                :key="ph.id"
                role="listitem"
                class="rounded-control px-3 py-1.5 text-sm font-medium transition duration-fast ease-out"
                :class="ph.phase === profile.current_phase
                    ? 'bg-brand text-white shadow-soft'
                    : 'bg-app text-ink-muted ring-1 ring-border/70'"
            >
                {{ phaseLabel(ph.phase) }}
            </span>
        </div>

        <SectionCard v-if="currentPhase" :title="`${phaseLabel(currentPhase.phase)} — checklist`" flush>
            <ul class="divide-y divide-border/60">
                <li
                    v-for="d in currentPhase.deliverables"
                    :key="d.id"
                    class="flex items-center justify-between gap-3 px-4 py-3 text-sm sm:px-5"
                >
                    <span class="min-w-0 text-ink" :class="d.completed ? 'text-ink-muted line-through' : ''">
                        {{ d.label }}
                        <span v-if="d.required" class="text-danger">*</span>
                    </span>
                    <label v-if="canManage" class="flex shrink-0 items-center gap-2 text-ink-muted">
                        <input type="checkbox" class="rounded border-border text-brand focus:ring-brand" :checked="d.completed" @change="toggleDeliverable(d)" />
                        Done
                    </label>
                    <StatusBadge v-else :value="d.completed ? 'done' : 'pending'" />
                </li>
            </ul>

            <div v-if="canApproveGate && currentGate && profile.current_phase !== 'CLOSED'" class="mt-0 space-y-3 border-t border-border px-4 py-4 sm:px-5">
                <h4 class="text-sm font-semibold text-ink">Stage approval</h4>
                <InputError :message="page.props.errors?.gate" />
                <TextareaInput v-model="gateForm.comments" :rows="2" placeholder="Comments" />
                <div class="flex flex-wrap gap-2">
                    <SecondaryButton type="button" @click.prevent="approveGate" :disabled="gateForm.processing">Approve stage</SecondaryButton>
                </div>
                <div class="flex flex-wrap items-end gap-2">
                    <div class="min-w-0 flex-1">
                        <InputLabel value="Rejection reason" />
                        <TextInput v-model="rejectForm.rejection_reason" class="mt-1 block w-full" />
                    </div>
                    <DangerButton type="button" @click.prevent="rejectGate" :disabled="rejectForm.processing">Reject</DangerButton>
                </div>
            </div>
        </SectionCard>

        <SectionCard v-if="canManage && profile.current_phase === 'MEASURE'" title="Add measurement plan">
            <form @submit.prevent="saveMeasure" class="grid gap-3 sm:grid-cols-2">
                <div><InputLabel value="Metric" /><TextInput v-model="measureForm.metric_name" class="mt-1 block w-full" required /></div>
                <div><InputLabel value="Baseline" /><TextInput v-model="measureForm.baseline_value" type="number" step="0.0001" class="mt-1 block w-full" /></div>
                <div class="sm:col-span-2"><PrimaryButton :disabled="measureForm.processing">Save plan</PrimaryButton></div>
            </form>
            <ul class="mt-4 divide-y divide-border text-sm">
                <li v-for="m in profile.measurement_plans" :key="m.id" class="py-2 text-ink-muted">{{ m.metric_name }} — baseline {{ m.baseline_value ?? '—' }}</li>
            </ul>
        </SectionCard>

        <SectionCard v-if="canManage && ['ANALYZE','IMPROVE','CONTROL','MEASURE','DEFINE'].includes(profile.current_phase)" title="Root causes">
            <form @submit.prevent="saveRoot" class="grid gap-3 sm:grid-cols-2">
                <div><InputLabel value="Title" /><TextInput v-model="rootForm.title" class="mt-1 block w-full" required /></div>
                <div>
                    <InputLabel value="Status" />
                    <SelectInput v-model="rootForm.status" class="mt-1">
                        <option value="suspected">suspected</option>
                        <option value="validated">validated</option>
                        <option value="rejected">rejected</option>
                    </SelectInput>
                </div>
                <div><InputLabel value="Evidence title" /><TextInput v-model="rootForm.evidence_title" class="mt-1 block w-full" /></div>
                <div><InputLabel value="Evidence" /><TextInput v-model="rootForm.evidence_body" class="mt-1 block w-full" /></div>
                <div class="sm:col-span-2"><PrimaryButton :disabled="rootForm.processing">Add root cause</PrimaryButton></div>
            </form>
            <ul class="mt-4 divide-y divide-border text-sm">
                <li v-for="r in profile.root_causes" :key="r.id" class="flex justify-between py-2">
                    <span class="text-ink">{{ r.title }} ({{ r.evidence?.length || 0 }} evidence)</span>
                    <StatusBadge :value="r.status" />
                </li>
            </ul>
        </SectionCard>

        <SectionCard v-if="canManage" title="Improvements (fixes)">
            <p class="mb-3 text-xs text-ink-muted">Register a fix here; deliver it via Plan → Build → Ship on this same project.</p>
            <form @submit.prevent="saveImprove" class="grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2"><InputLabel value="Title" /><TextInput v-model="improveForm.title" class="mt-1 block w-full" required /></div>
                <div>
                    <InputLabel value="Root cause" />
                    <SelectInput v-model="improveForm.dmaic_root_cause_id" class="mt-1">
                        <option value="">—</option>
                        <option v-for="r in profile.root_causes" :key="r.id" :value="r.id">{{ r.title }}</option>
                    </SelectInput>
                </div>
                <div>
                    <InputLabel value="Status" />
                    <SelectInput v-model="improveForm.status" class="mt-1">
                        <option value="planned">planned</option>
                        <option value="implemented">implemented</option>
                        <option value="verified">verified</option>
                        <option value="completed">completed</option>
                    </SelectInput>
                </div>
                <div><InputLabel value="Before" /><TextInput v-model="improveForm.before_measurement" type="number" step="0.0001" class="mt-1 block w-full" /></div>
                <div><InputLabel value="After" /><TextInput v-model="improveForm.after_measurement" type="number" step="0.0001" class="mt-1 block w-full" /></div>
                <label class="flex items-center gap-2 text-sm text-ink-muted sm:col-span-2">
                    <input type="checkbox" v-model="improveForm.create_feature" class="rounded border-border text-brand focus:ring-brand" />
                    Create linked feature
                </label>
                <div class="sm:col-span-2"><PrimaryButton :disabled="improveForm.processing">Register improvement</PrimaryButton></div>
            </form>
            <ul class="mt-4 divide-y divide-border text-sm">
                <li v-for="i in profile.improvements" :key="i.id" class="py-2 text-ink-muted">{{ i.title }} → {{ i.feature?.title || 'no feature' }} · {{ i.status }}</li>
            </ul>
        </SectionCard>

        <SectionCard v-if="canManage" title="Control plan">
            <form @submit.prevent="saveControl" class="grid gap-3 sm:grid-cols-2">
                <div><InputLabel value="Plan title" /><TextInput v-model="controlForm.title" class="mt-1 block w-full" required /></div>
                <div><InputLabel value="Metric" /><TextInput v-model="controlForm.metric_name" class="mt-1 block w-full" required /></div>
                <div><InputLabel value="Current" /><TextInput v-model="controlForm.current_value" type="number" step="0.0001" class="mt-1 block w-full" /></div>
                <div><InputLabel value="UCL" /><TextInput v-model="controlForm.ucl" type="number" step="0.0001" class="mt-1 block w-full" /></div>
                <div><InputLabel value="LCL" /><TextInput v-model="controlForm.lcl" type="number" step="0.0001" class="mt-1 block w-full" /></div>
                <div class="sm:col-span-2"><PrimaryButton :disabled="controlForm.processing">Save control plan</PrimaryButton></div>
            </form>
            <div v-for="plan in profile.control_plans" :key="plan.id" class="mt-4 border-t border-border pt-3 text-sm">
                <div class="font-medium text-ink">{{ plan.title }}</div>
                <ul>
                    <li v-for="m in plan.metrics" :key="m.id" class="flex justify-between py-1 text-ink-muted">
                        <span>{{ m.metric_name }} = {{ m.current_value ?? '—' }} (LCL {{ m.lcl ?? '—' }} / UCL {{ m.ucl ?? '—' }})</span>
                        <span v-if="m.alerts?.length" class="text-danger">{{ m.alerts.length }} alert(s)</span>
                    </li>
                </ul>
            </div>
        </SectionCard>
    </div>
</template>

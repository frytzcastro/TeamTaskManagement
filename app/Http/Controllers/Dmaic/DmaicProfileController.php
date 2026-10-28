<?php

namespace App\Http\Controllers\Dmaic;

use App\Http\Controllers\Controller;
use App\Models\BusinessProblem;
use App\Models\DeliveryProject;
use App\Models\Dmaic\DmaicControlMetric;
use App\Models\Dmaic\DmaicControlPlan;
use App\Models\Dmaic\DmaicGate;
use App\Models\Dmaic\DmaicImprovement;
use App\Models\Dmaic\DmaicMeasurementPlan;
use App\Models\Dmaic\DmaicPhaseDeliverable;
use App\Models\Dmaic\DmaicProfile;
use App\Models\Dmaic\DmaicRootCause;
use App\Models\Feature;
use App\Models\Kpi;
use App\Models\User;
use App\Services\DmaicWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DmaicProfileController extends Controller
{
    public function __construct(private DmaicWorkflowService $workflow) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DmaicProfile::class);

        return Inertia::render('Dmaic/Index', [
            'profiles' => DmaicProfile::with(['deliveryProject', 'leader', 'businessProblem'])
                ->latest()
                ->paginate(25),
            'canManage' => $request->user()->can('dmaic.manage'),
        ]);
    }

    public function show(DmaicProfile $dmaic): RedirectResponse
    {
        $this->authorize('view', $dmaic);

        return redirect()->route('delivery-projects.show', [
            'deliveryProject' => $dmaic->delivery_project_id,
            'tab' => 'improve',
        ]);
    }

    public function bootstrap(Request $request, DeliveryProject $deliveryProject): RedirectResponse
    {
        $this->authorize('create', DmaicProfile::class);
        abort_unless($deliveryProject->type === 'dmaic', 422, 'Project must be type dmaic.');

        $data = $request->validate([
            'business_problem_id' => ['nullable', 'exists:business_problems,id'],
        ]);

        $profile = $this->workflow->bootstrap(
            $deliveryProject,
            $data['business_problem_id'] ?? null,
            $request->user()
        );

        return redirect()
            ->route('delivery-projects.show', ['deliveryProject' => $deliveryProject->id, 'tab' => 'improve'])
            ->with('success', 'Improvement workspace initialized.');
    }

    public function updateDeliverable(Request $request, DmaicPhaseDeliverable $deliverable): RedirectResponse
    {
        $profile = $deliverable->phase->profile;
        $this->authorize('update', $profile);

        $data = $request->validate([
            'completed' => ['required', 'boolean'],
            'evidence_url' => ['nullable', 'string', 'max:255'],
            'value' => ['nullable', 'array'],
        ]);

        $deliverable->update($data);

        return redirect()->back()->with('success', 'Deliverable updated.');
    }

    public function approveGate(Request $request, DmaicGate $gate): RedirectResponse
    {
        $profile = $gate->phase->profile;
        $this->authorize('approveGate', $profile);

        $data = $request->validate([
            'comments' => ['nullable', 'string'],
        ]);

        try {
            $this->workflow->approveGate($gate, $request->user(), $data['comments'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['gate' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Stage approved; cycle advanced.');
    }

    public function rejectGate(Request $request, DmaicGate $gate): RedirectResponse
    {
        $profile = $gate->phase->profile;
        $this->authorize('approveGate', $profile);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $this->workflow->rejectGate($gate, $request->user(), $data['rejection_reason']);

        return redirect()->back()->with('success', 'Stage rejected.');
    }

    public function storeMeasurementPlan(Request $request, DmaicProfile $dmaic): RedirectResponse
    {
        $this->authorize('update', $dmaic);

        $data = $request->validate([
            'metric_name' => ['required', 'string', 'max:255'],
            'definition' => ['nullable', 'string'],
            'formula' => ['nullable', 'string'],
            'data_source' => ['nullable', 'string', 'max:255'],
            'frequency' => ['nullable', 'string', 'max:100'],
            'baseline_value' => ['nullable', 'numeric'],
            'data_quality_status' => ['nullable', 'string', 'max:50'],
            'collection_method' => ['nullable', 'string'],
            'sample_size' => ['nullable', 'integer', 'min:0'],
        ]);

        $dmaic->measurementPlans()->create($data);

        $dmaic->phases()->where('phase', 'MEASURE')->first()?->deliverables()
            ->where('key', 'measurement_plan')
            ->update(['completed' => filled($data['baseline_value'] ?? null)]);

        return redirect()->back()->with('success', 'Measurement plan added.');
    }

    public function storeRootCause(Request $request, DmaicProfile $dmaic): RedirectResponse
    {
        $this->authorize('update', $dmaic);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:'.implode(',', DmaicRootCause::STATUSES)],
            'evidence_title' => ['nullable', 'string', 'max:255'],
            'evidence_body' => ['nullable', 'string'],
        ]);

        $cause = $dmaic->rootCauses()->create([
            'title' => $data['title'],
            'method' => $data['method'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'suspected',
        ]);

        if (! empty($data['evidence_title'])) {
            $cause->evidence()->create([
                'title' => $data['evidence_title'],
                'body' => $data['evidence_body'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);
        }

        $this->refreshAnalyzeDeliverable($dmaic);

        return redirect()->back()->with('success', 'Root cause recorded.');
    }

    public function updateRootCause(Request $request, DmaicRootCause $rootCause): RedirectResponse
    {
        $this->authorize('update', $rootCause->profile);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', DmaicRootCause::STATUSES)],
            'evidence_title' => ['nullable', 'string', 'max:255'],
            'evidence_body' => ['nullable', 'string'],
        ]);

        $rootCause->update(['status' => $data['status']]);

        if (! empty($data['evidence_title'])) {
            $rootCause->evidence()->create([
                'title' => $data['evidence_title'],
                'body' => $data['evidence_body'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);
        }

        $this->refreshAnalyzeDeliverable($rootCause->profile);

        return redirect()->back()->with('success', 'Root cause updated.');
    }

    public function storeImprovement(Request $request, DmaicProfile $dmaic): RedirectResponse
    {
        $this->authorize('update', $dmaic);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'dmaic_root_cause_id' => ['nullable', 'exists:dmaic_root_causes,id'],
            'feature_id' => ['nullable', 'exists:features,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'expected_impact' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'is_pilot' => ['nullable', 'boolean'],
            'before_measurement' => ['nullable', 'numeric'],
            'expected_measurement' => ['nullable', 'numeric'],
            'after_measurement' => ['nullable', 'numeric'],
            'create_feature' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['create_feature']) && empty($data['feature_id'])) {
            $feature = Feature::create([
                'delivery_project_id' => $dmaic->delivery_project_id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => 'planned',
                'priority' => 'high',
            ]);
            $data['feature_id'] = $feature->id;
        }

        unset($data['create_feature']);
        $dmaic->improvements()->create($data);

        $dmaic->phases()->where('phase', 'IMPROVE')->first()?->deliverables()
            ->where('key', 'improvements_implemented')
            ->update([
                'completed' => $dmaic->improvements()
                    ->whereIn('status', ['implemented', 'verified', 'completed'])
                    ->whereNotNull('after_measurement')
                    ->exists(),
            ]);

        return redirect()->back()->with('success', 'Improvement registered.');
    }

    public function storeControlPlan(Request $request, DmaicProfile $dmaic): RedirectResponse
    {
        $this->authorize('update', $dmaic);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'metric_name' => ['required', 'string', 'max:255'],
            'kpi_id' => ['nullable', 'exists:kpis,id'],
            'baseline' => ['nullable', 'numeric'],
            'target' => ['nullable', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'ucl' => ['nullable', 'numeric'],
            'lcl' => ['nullable', 'numeric'],
            'frequency' => ['nullable', 'string', 'max:100'],
        ]);

        $plan = $dmaic->controlPlans()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'owner_id' => $data['owner_id'] ?? null,
            'status' => 'active',
        ]);

        $metric = $plan->metrics()->create([
            'metric_name' => $data['metric_name'],
            'kpi_id' => $data['kpi_id'] ?? null,
            'baseline' => $data['baseline'] ?? null,
            'target' => $data['target'] ?? null,
            'current_value' => $data['current_value'] ?? null,
            'ucl' => $data['ucl'] ?? null,
            'lcl' => $data['lcl'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'owner_id' => $data['owner_id'] ?? null,
        ]);

        $this->workflow->evaluateControlMetric($metric);

        $dmaic->phases()->where('phase', 'CONTROL')->first()?->deliverables()
            ->whereIn('key', ['control_plan', 'monitoring'])
            ->update(['completed' => true]);

        return redirect()->back()->with('success', 'Control plan created.');
    }

    public function updateControlMetric(Request $request, DmaicControlMetric $metric): RedirectResponse
    {
        $profile = $metric->controlPlan->profile;
        $this->authorize('update', $profile);

        $data = $request->validate([
            'current_value' => ['required', 'numeric'],
        ]);

        $metric->update($data);
        $this->workflow->evaluateControlMetric($metric->fresh());

        return redirect()->back()->with('success', 'Control metric updated.');
    }

    private function refreshAnalyzeDeliverable(DmaicProfile $dmaic): void
    {
        $ok = $dmaic->rootCauses()->where('status', 'validated')->whereHas('evidence')->exists();
        $dmaic->phases()->where('phase', 'ANALYZE')->first()?->deliverables()
            ->where('key', 'validated_root_causes')
            ->update(['completed' => $ok]);
    }
}

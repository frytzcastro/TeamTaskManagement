<?php

namespace App\Services;

use App\Models\DeliveryProject;
use App\Models\Dmaic\DmaicControlAlert;
use App\Models\Dmaic\DmaicControlMetric;
use App\Models\Dmaic\DmaicGate;
use App\Models\Dmaic\DmaicPhase;
use App\Models\Dmaic\DmaicProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DmaicWorkflowService
{
    /**
     * @var array<string, list<array{key: string, label: string, required: bool}>>
     */
    public const PHASE_DELIVERABLES = [
        'DEFINE' => [
            ['key' => 'problem_statement', 'label' => 'Problem statement', 'required' => true],
            ['key' => 'business_case', 'label' => 'Business case / impact', 'required' => true],
            ['key' => 'goal', 'label' => 'Project goal', 'required' => true],
            ['key' => 'scope', 'label' => 'Scope / out of scope', 'required' => true],
            ['key' => 'stakeholders', 'label' => 'Stakeholders / owners', 'required' => true],
            ['key' => 'initial_kpi', 'label' => 'Initial KPI / success criteria', 'required' => true],
        ],
        'MEASURE' => [
            ['key' => 'measurement_plan', 'label' => 'Measurement plan with baseline', 'required' => true],
            ['key' => 'data_quality', 'label' => 'Data quality confirmed', 'required' => true],
        ],
        'ANALYZE' => [
            ['key' => 'validated_root_causes', 'label' => 'Validated root causes with evidence', 'required' => true],
        ],
        'IMPROVE' => [
            ['key' => 'improvements_implemented', 'label' => 'Improvements implemented and measured', 'required' => true],
        ],
        'CONTROL' => [
            ['key' => 'control_plan', 'label' => 'Control plan active', 'required' => true],
            ['key' => 'monitoring', 'label' => 'Monitoring ownership assigned', 'required' => true],
        ],
    ];

    public function bootstrap(DeliveryProject $deliveryProject, ?string $businessProblemId = null, ?User $leader = null): DmaicProfile
    {
        if ($deliveryProject->type !== 'dmaic') {
            throw new InvalidArgumentException('DMAIC profile requires delivery project type=dmaic.');
        }

        return DB::transaction(function () use ($deliveryProject, $businessProblemId, $leader) {
            $profile = DmaicProfile::query()->firstOrCreate(
                ['delivery_project_id' => $deliveryProject->id],
                [
                    'business_problem_id' => $businessProblemId,
                    'current_phase' => 'DEFINE',
                    'status' => 'active',
                    'leader_id' => $leader?->id ?? $deliveryProject->project_owner_id,
                    'sponsor_id' => $deliveryProject->product_owner_id,
                    'process_owner_id' => $deliveryProject->product_owner_id,
                ]
            );

            foreach (DmaicProfile::PHASES as $phaseName) {
                if ($phaseName === 'CLOSED') {
                    continue;
                }

                $phase = DmaicPhase::query()->firstOrCreate(
                    [
                        'dmaic_profile_id' => $profile->id,
                        'phase' => $phaseName,
                    ],
                    [
                        'status' => $phaseName === 'DEFINE' ? 'in_progress' : 'pending',
                    ]
                );

                foreach (self::PHASE_DELIVERABLES[$phaseName] ?? [] as $deliverable) {
                    $phase->deliverables()->firstOrCreate(
                        ['key' => $deliverable['key']],
                        [
                            'label' => $deliverable['label'],
                            'required' => $deliverable['required'],
                            'completed' => false,
                        ]
                    );
                }

                if (! $phase->gates()->exists()) {
                    $phase->gates()->create(['status' => 'pending']);
                }
            }

            return $profile->fresh(['phases.deliverables', 'phases.gates']);
        });
    }

    public function requiredDeliverablesComplete(DmaicPhase $phase): bool
    {
        $required = $phase->deliverables()->where('required', true)->get();

        if ($required->isEmpty()) {
            return true;
        }

        return $required->every(fn ($d) => $d->completed);
    }

    public function phaseSpecificRequirementsMet(DmaicProfile $profile, string $phase): bool
    {
        return match ($phase) {
            'MEASURE' => $profile->measurementPlans()->whereNotNull('baseline_value')->exists(),
            'ANALYZE' => $profile->rootCauses()
                ->where('status', 'validated')
                ->whereHas('evidence')
                ->exists(),
            'IMPROVE' => $profile->improvements()
                ->whereIn('status', ['implemented', 'verified', 'completed'])
                ->whereNotNull('after_measurement')
                ->exists(),
            'CONTROL' => $profile->controlPlans()->where('status', 'active')->whereHas('metrics')->exists(),
            default => true,
        };
    }

    public function canSubmitGate(DmaicProfile $profile, DmaicPhase $phase): bool
    {
        return $this->requiredDeliverablesComplete($phase)
            && $this->phaseSpecificRequirementsMet($profile, $phase->phase);
    }

    public function approveGate(DmaicGate $gate, User $reviewer, ?string $comments = null): DmaicProfile
    {
        $phase = $gate->phase()->with('profile')->firstOrFail();
        $profile = $phase->profile;

        if (! $this->canSubmitGate($profile, $phase)) {
            throw new InvalidArgumentException('Required deliverables or phase criteria are incomplete.');
        }

        if ($profile->current_phase !== $phase->phase) {
            throw new InvalidArgumentException('Gate is not for the current DMAIC phase.');
        }

        return DB::transaction(function () use ($gate, $reviewer, $comments, $phase, $profile) {
            $gate->update([
                'status' => 'approved',
                'reviewer_id' => $reviewer->id,
                'approved_at' => now(),
                'comments' => $comments,
                'rejection_reason' => null,
            ]);

            $phase->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $next = $this->nextPhase($phase->phase);
            $profile->update(['current_phase' => $next]);

            if ($next === 'CLOSED') {
                $profile->update(['status' => 'closed']);
            } else {
                DmaicPhase::query()
                    ->where('dmaic_profile_id', $profile->id)
                    ->where('phase', $next)
                    ->update(['status' => 'in_progress']);
            }

            return $profile->fresh(['phases.deliverables', 'phases.gates']);
        });
    }

    public function rejectGate(DmaicGate $gate, User $reviewer, string $reason): DmaicGate
    {
        $gate->update([
            'status' => 'rejected',
            'reviewer_id' => $reviewer->id,
            'rejection_reason' => $reason,
            'approved_at' => null,
        ]);

        return $gate->fresh();
    }

    public function nextPhase(string $phase): string
    {
        $order = DmaicProfile::PHASES;
        $index = array_search($phase, $order, true);

        if ($index === false || $index >= count($order) - 1) {
            return 'CLOSED';
        }

        return $order[$index + 1];
    }

    public function evaluateControlMetric(DmaicControlMetric $metric): ?DmaicControlAlert
    {
        if (! $metric->isBreached()) {
            return null;
        }

        return DmaicControlAlert::query()->create([
            'dmaic_control_metric_id' => $metric->id,
            'severity' => 'high',
            'message' => "Control breach on {$metric->metric_name}: current {$metric->current_value}",
            'status' => 'open',
            'triggered_at' => now(),
        ]);
    }
}

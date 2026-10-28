<?php

namespace Database\Seeders;

use App\Models\Bug;
use App\Models\BusinessProblem;
use App\Models\DeliveryProject;
use App\Models\Epic;
use App\Models\Feature;
use App\Models\Initiative;
use App\Models\Kpi;
use App\Models\System;
use App\Models\Release;
use App\Models\Sprint;
use App\Models\StrategicPillar;
use App\Models\SupportSla;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\DeliveryMetricsService;
use App\Services\DeliveryProjectHealthService;
use App\Services\DmaicWorkflowService;
use App\Services\MilestoneSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        $pm = User::query()->updateOrCreate(
            ['email' => 'pm@example.com'],
            [
                'name' => 'Pat Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $pm->syncRoles(['project_manager']);

        $dev = User::query()->updateOrCreate(
            ['email' => 'dev@example.com'],
            [
                'name' => 'Dana Developer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $dev->syncRoles(['developer']);

        $po = User::query()->updateOrCreate(
            ['email' => 'po@example.com'],
            [
                'name' => 'Perry Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $po->syncRoles(['product_owner']);

        $exec = User::query()->updateOrCreate(
            ['email' => 'exec@example.com'],
            [
                'name' => 'Eden Executive',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $exec->syncRoles(['executive']);

        $improvementLead = User::query()->updateOrCreate(
            ['email' => 'il@example.com'],
            [
                'name' => 'Ira Lead',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $improvementLead->syncRoles(['improvement_lead']);

        $supportUser = User::query()->updateOrCreate(
            ['email' => 'support@example.com'],
            [
                'name' => 'Susie Support',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $supportUser->syncRoles(['support']);

        foreach ([
            ['priority' => 'critical', 'response_minutes' => 15, 'resolution_minutes' => 240],
            ['priority' => 'high', 'response_minutes' => 60, 'resolution_minutes' => 480],
            ['priority' => 'medium', 'response_minutes' => 240, 'resolution_minutes' => 2880],
            ['priority' => 'low', 'response_minutes' => 1440, 'resolution_minutes' => 7200],
        ] as $sla) {
            SupportSla::query()->updateOrCreate(['priority' => $sla['priority']], $sla);
        }

        $team = Team::query()->updateOrCreate(
            ['name' => 'Digital Product Team'],
            ['description' => 'Core delivery team']
        );
        $team->users()->syncWithoutDetaching([
            $pm->id => ['role' => 'lead'],
            $dev->id => ['role' => 'developer'],
            $po->id => ['role' => 'product'],
        ]);

        $reservationApp = System::query()->updateOrCreate(
            ['code' => 'RES-APP'],
            [
                'name' => 'Reservation App',
                'type' => 'app',
                'description' => 'Customer-facing reservation experience.',
                'owner_id' => $po->id,
                'status' => 'active',
            ]
        );

        $sellerPortal = System::query()->updateOrCreate(
            ['code' => 'SELLER-PORTAL'],
            [
                'name' => 'Seller Portal',
                'type' => 'app',
                'description' => 'Seller operations and reservation management.',
                'owner_id' => $po->id,
                'status' => 'active',
            ]
        );

        $pillar = StrategicPillar::query()->updateOrCreate(
            ['name' => 'Cancellation Prevention'],
            [
                'description' => 'Reduce preventable cancellations across the reservation funnel.',
                'status' => 'active',
            ]
        );

        $kpi = Kpi::query()->updateOrCreate(
            ['code' => 'CANCEL_RATE'],
            [
                'name' => 'Cancellation Rate',
                'metric_layer' => 'business',
                'definition' => 'Share of reservations that cancel before take-out.',
                'formula' => 'Cancelled Reservations ÷ Total Reservations',
                'owner_id' => $po->id,
                'baseline' => 10,
                'target' => 8.5,
                'current_value' => 9.1,
                'reporting_period' => 'monthly',
                'direction' => 'lower_is_better',
                'status' => 'amber',
                'trend' => 'improving',
                'unit' => '%',
            ]
        );

        $initiative = Initiative::query()->updateOrCreate(
            ['code' => 'INIT-004'],
            [
                'strategic_pillar_id' => $pillar->id,
                'name' => 'Cancellation Risk Indicators',
                'owner_id' => $po->id,
                'start_date' => now()->subMonths(1)->toDateString(),
                'target_date' => now()->addMonths(2)->toDateString(),
                'priority' => 'high',
                'status' => 'active',
                'business_goal' => 'Reduce preventable cancellations',
                'problem' => 'Late identification of cancellation risk',
                'opportunity' => 'Surface risk signals earlier to sellers and ops',
                'expected_business_impact' => 'Revenue retention',
                'success_criteria' => 'Cancellation rate moves toward 8.5%',
            ]
        );
        $initiative->kpis()->syncWithoutDetaching([$kpi->id]);

        $deliveryProject = DeliveryProject::query()->updateOrCreate(
            ['code' => 'PRJ-CANCEL-RISK'],
            [
                'initiative_id' => $initiative->id,
                'type' => 'standard',
                'name' => 'Cancellation Risk Engine',
                'project_owner_id' => $pm->id,
                'product_owner_id' => $po->id,
                'team_id' => $team->id,
                'start_date' => now()->subWeeks(3)->toDateString(),
                'target_completion' => now()->addWeeks(6)->toDateString(),
                'status' => 'active',
                'health' => 'green',
                'priority' => 'high',
                'description' => 'Build risk rules API and frontend indicators.',
                'success_criteria' => 'Feature released and cancellation rate improves.',
            ]
        );
        $deliveryProject->kpis()->syncWithoutDetaching([$kpi->id]);
        $deliveryProject->members()->syncWithoutDetaching([$pm->id, $dev->id, $po->id]);
        $initiative->systems()->syncWithoutDetaching([$reservationApp->id, $sellerPortal->id]);

        $epic = Epic::query()->updateOrCreate(
            [
                'system_id' => $reservationApp->id,
                'title' => 'Risk Rules API live',
            ],
            [
                'description' => 'Ship risk rules API and seller-facing indicators.',
                'priority' => 'high',
                'status' => 'in_progress',
                'start_date' => now()->subWeeks(2)->toDateString(),
                'due_date' => now()->addWeeks(2)->toDateString(),
            ]
        );

        $feature = Feature::query()->updateOrCreate(
            [
                'system_id' => $reservationApp->id,
                'title' => 'Risk Engine Feature',
            ],
            [
                'delivery_project_id' => $deliveryProject->id,
                'epic_id' => $epic->id,
                'description' => 'Cancellation risk indicators for sellers.',
                'acceptance_criteria' => 'Risk score visible on reservation detail.',
                'priority' => 'high',
                'status' => 'in_progress',
                'dod_checklist' => Feature::defaultDodChecklist(),
            ]
        );

        $tasks = [
            ['title' => 'Database analysis', 'status' => 'done', 'priority' => 'high'],
            ['title' => 'Risk rules API', 'status' => 'in_progress', 'priority' => 'critical'],
            ['title' => 'Frontend indicators', 'status' => 'ready', 'priority' => 'high'],
            ['title' => 'QA testing', 'status' => 'backlog', 'priority' => 'medium'],
        ];

        foreach ($tasks as $index => $taskData) {
            $task = Task::query()->updateOrCreate(
                [
                    'delivery_project_id' => $deliveryProject->id,
                    'title' => $taskData['title'],
                ],
                [
                    'feature_id' => $feature->id,
                    'assignee_id' => $dev->id,
                    'priority' => $taskData['priority'],
                    'status' => $taskData['status'],
                    'due_date' => now()->addDays(3 + ($index * 2))->toDateString(),
                    'estimate' => 8,
                    'completion_percent' => $taskData['status'] === 'done' ? 100 : ($taskData['status'] === 'in_progress' ? 40 : 0),
                ]
            );
            app(DeliveryMetricsService::class)->applyTaskTimestamps($task);
            $task->save();
        }

        $riskApiTask = Task::query()
            ->where('delivery_project_id', $deliveryProject->id)
            ->where('title', 'Risk rules API')
            ->first();

        Bug::query()->updateOrCreate(
            ['title' => 'Risk badge missing on production reservation detail'],
            [
                'description' => 'Sellers cannot see cancellation risk on live reservations.',
                'steps_to_reproduce' => "1. Open Reservation App\n2. Open a reservation\n3. Look for risk badge",
                'expected_result' => 'Risk score badge is visible',
                'actual_result' => 'Badge is missing',
                'environment' => 'production',
                'severity' => 'critical',
                'status' => 'assigned',
                'reporter_id' => $po->id,
                'assignee_id' => $dev->id,
                'delivery_project_id' => $deliveryProject->id,
                'feature_id' => $feature->id,
                'system_id' => $reservationApp->id,
                'task_id' => $riskApiTask?->id,
            ]
        );

        $resolvedBug = Bug::query()->updateOrCreate(
            ['title' => 'Staging risk API returns 500 on empty payload'],
            [
                'description' => 'Empty body crashes risk rules endpoint.',
                'environment' => 'staging',
                'severity' => 'high',
                'status' => 'resolved',
                'reporter_id' => $pm->id,
                'assignee_id' => $dev->id,
                'delivery_project_id' => $deliveryProject->id,
                'system_id' => $reservationApp->id,
                'resolved_at' => now()->subDays(2),
            ]
        );

        $reopenedBug = Bug::query()->updateOrCreate(
            ['title' => 'Seller Portal risk tooltip overflow'],
            [
                'description' => 'Tooltip clips on smaller screens.',
                'environment' => 'web',
                'severity' => 'medium',
                'status' => 'reopened',
                'reporter_id' => $po->id,
                'assignee_id' => $dev->id,
                'delivery_project_id' => $deliveryProject->id,
                'system_id' => $sellerPortal->id,
                'reopened_count' => 1,
                'resolved_at' => null,
                'closed_at' => null,
            ]
        );
        unset($resolvedBug, $reopenedBug);

        $problem = BusinessProblem::query()->updateOrCreate(
            [
                'initiative_id' => $initiative->id,
                'title' => 'Late cancellation risk detection',
            ],
            [
                'problem_statement' => 'Sellers discover cancellation risk too late to intervene.',
                'business_impact' => 'Lost take-out revenue and inventory waste.',
                'customer_impact' => 'Guests cancel without remediation offers.',
                'opportunity' => 'Surface risk earlier with actionable interventions.',
                'status' => 'open',
                'owner_id' => $po->id,
            ]
        );

        $dmaicProject = DeliveryProject::query()->updateOrCreate(
            ['code' => 'PRJ-CANCEL-DMAIC'],
            [
                'initiative_id' => $initiative->id,
                'type' => 'dmaic',
                'name' => 'Cancellation DMAIC',
                'project_owner_id' => $improvementLead->id,
                'product_owner_id' => $po->id,
                'team_id' => $team->id,
                'start_date' => now()->subWeeks(1)->toDateString(),
                'target_completion' => now()->addMonths(3)->toDateString(),
                'status' => 'active',
                'health' => 'green',
                'priority' => 'high',
                'description' => 'DMAIC cycle to reduce preventable cancellations.',
                'success_criteria' => 'Control plan sustains cancellation rate ≤ 8.5%.',
            ]
        );
        $dmaicProject->kpis()->syncWithoutDetaching([$kpi->id]);
        $dmaicProject->members()->syncWithoutDetaching([$improvementLead->id, $pm->id, $po->id, $dev->id]);
        $initiative->systems()->syncWithoutDetaching([$reservationApp->id]);

        $profile = app(DmaicWorkflowService::class)->bootstrap($dmaicProject, $problem->id, $improvementLead);

        $definePhase = $profile->phases()->where('phase', 'DEFINE')->first();
        $definePhase?->deliverables()->update(['completed' => true]);

        $profile->measurementPlans()->updateOrCreate(
            ['metric_name' => 'Cancellation Rate'],
            [
                'definition' => 'Cancelled ÷ total reservations',
                'baseline_value' => 10,
                'data_quality_status' => 'good',
                'frequency' => 'weekly',
            ]
        );

        $rootCause = $profile->rootCauses()->updateOrCreate(
            ['title' => 'Risk signals arrive after payment confirmation'],
            [
                'method' => '5 Whys',
                'description' => 'Risk scoring runs too late in the booking funnel.',
                'status' => 'validated',
            ]
        );
        $rootCause->evidence()->updateOrCreate(
            ['title' => 'Funnel timing analysis'],
            [
                'body' => 'Median risk compute is 4h after booking confirmation.',
                'recorded_by' => $improvementLead->id,
            ]
        );

        $improveFeature = Feature::query()->updateOrCreate(
            [
                'system_id' => $reservationApp->id,
                'title' => 'Pre-confirm risk check',
            ],
            [
                'delivery_project_id' => $dmaicProject->id,
                'description' => 'Compute risk before payment confirmation.',
                'priority' => 'high',
                'status' => 'planned',
                'dod_checklist' => Feature::defaultDodChecklist(),
            ]
        );

        $profile->improvements()->updateOrCreate(
            ['title' => 'Move risk scoring before confirmation'],
            [
                'dmaic_root_cause_id' => $rootCause->id,
                'feature_id' => $improveFeature->id,
                'description' => 'Engineering change to score earlier.',
                'owner_id' => $dev->id,
                'expected_impact' => 'Earlier seller intervention',
                'status' => 'planned',
                'before_measurement' => 10,
                'expected_measurement' => 8.5,
            ]
        );

        Bug::query()->updateOrCreate(
            ['title' => 'Risk job skips pre-confirm path'],
            [
                'description' => 'Pre-confirm risk endpoint not invoked in staging.',
                'environment' => 'staging',
                'severity' => 'high',
                'status' => 'reported',
                'reporter_id' => $improvementLead->id,
                'assignee_id' => $dev->id,
                'delivery_project_id' => $dmaicProject->id,
                'feature_id' => $improveFeature->id,
                'system_id' => $reservationApp->id,
                'dmaic_profile_id' => $profile->id,
                'dmaic_root_cause_id' => $rootCause->id,
            ]
        );

        $feature->update(['dod_checklist' => Feature::defaultDodChecklist()]);

        $sprint = Sprint::query()->updateOrCreate(
            [
                'delivery_project_id' => $deliveryProject->id,
                'name' => 'Sprint 14 — Risk indicators',
            ],
            [
                'start_date' => now()->startOfWeek()->toDateString(),
                'end_date' => now()->startOfWeek()->addDays(13)->toDateString(),
                'status' => 'active',
                'goal' => 'Ship risk API and frontend badge.',
            ]
        );

        Task::query()
            ->where('delivery_project_id', $deliveryProject->id)
            ->update(['sprint_id' => $sprint->id]);

        $readyTask = Task::query()
            ->where('delivery_project_id', $deliveryProject->id)
            ->where('title', 'Frontend indicators')
            ->first();
        if ($readyTask) {
            $readyTask->update(['status' => 'ready_for_release']);
        }

        $release = Release::query()->updateOrCreate(
            [
                'delivery_project_id' => $deliveryProject->id,
                'name' => 'Risk Engine v0.1',
            ],
            [
                'system_id' => $reservationApp->id,
                'version' => '0.1.0',
                'environment' => 'staging',
                'status' => 'in_qa',
                'target_date' => now()->addWeek()->toDateString(),
                'notes' => 'First staging package for cancellation risk.',
            ]
        );
        if ($readyTask) {
            $release->items()->firstOrCreate([
                'item_type' => 'task',
                'item_id' => $readyTask->id,
            ]);
        }
        $release->items()->firstOrCreate([
            'item_type' => 'feature',
            'item_id' => $feature->id,
        ]);

        $ticket = new SupportTicket([
            'title' => 'Seller cannot see risk badge on live booking',
            'description' => 'Customer support report from seller ops.',
            'category' => 'system_issue',
            'priority' => 'high',
            'status' => 'open',
            'reporter_id' => $supportUser->id,
            'assignee_id' => $supportUser->id,
            'system_id' => $reservationApp->id,
            'delivery_project_id' => $deliveryProject->id,
        ]);
        $ticket->created_at = now();
        $ticket->applySlaFromPriority();
        $ticket->save();

        app(MilestoneSyncService::class)->reconcileProject($deliveryProject->fresh(['features', 'releases']));
        app(DeliveryProjectHealthService::class)->refresh($deliveryProject->fresh());
        app(DeliveryProjectHealthService::class)->refresh($dmaicProject->fresh());
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DeliveryProject;
use App\Models\DeliveryProjectMilestone;
use App\Models\Initiative;
use App\Models\Kpi;
use App\Models\Team;
use App\Models\User;
use App\Services\DeliveryProjectHealthService;
use App\Services\DmaicWorkflowService;
use App\Services\MilestoneSyncService;
use App\Services\ScheduleTimelineService;
use App\Services\TaskBoardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryProjectController extends Controller
{
    public function __construct(
        private DeliveryProjectHealthService $healthService,
        private DmaicWorkflowService $dmaicWorkflow,
        private ScheduleTimelineService $scheduleTimeline,
        private TaskBoardService $taskBoardService,
        private MilestoneSyncService $milestoneSync,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DeliveryProject::class);

        $deliveryProjects = DeliveryProject::query()
            ->with(['initiative.strategicPillar', 'projectOwner'])
            ->withCount('tasks')
            ->latest()
            ->get();

        return Inertia::render('DeliveryProjects/Index', [
            'deliveryProjects' => $deliveryProjects,
            'canManage' => $request->user()->can('delivery_projects.manage'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', DeliveryProject::class);

        return Inertia::render('DeliveryProjects/Form', [
            'deliveryProject' => null,
            'initiatives' => Initiative::with('systems:id,name')->orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'teams' => Team::orderBy('name')->get(['id', 'name']),
            'kpis' => Kpi::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', DeliveryProject::class);

        $data = $this->validated($request);
        $kpiIds = $data['kpi_ids'] ?? [];
        $memberIds = $data['member_ids'] ?? [];
        unset($data['kpi_ids'], $data['member_ids']);

        $deliveryProject = DeliveryProject::create($data);
        $deliveryProject->kpis()->sync($kpiIds);
        $deliveryProject->members()->sync($memberIds);
        $this->healthService->refresh($deliveryProject);

        if ($deliveryProject->type === 'dmaic') {
            $this->dmaicWorkflow->bootstrap($deliveryProject, null, $request->user());
        }

        return redirect()->route('delivery-projects.show', $deliveryProject)->with('success', 'Delivery project created.');
    }

    public function show(DeliveryProject $deliveryProject): Response
    {
        $this->authorize('view', $deliveryProject);

        $deliveryProject->load([
            'initiative.strategicPillar',
            'projectOwner',
            'productOwner',
            'team',
            'milestones',
            'members',
            'initiative.systems',
            'features.system',
            'features.epic',
            'tasks.assignee',
            'tasks.sprint',
            'tasks.feature',
            'kpis',
            'dmaicProfile.businessProblem',
            'dmaicProfile.phases.deliverables',
            'dmaicProfile.phases.gates.reviewer',
            'dmaicProfile.measurementPlans',
            'dmaicProfile.rootCauses.evidence',
            'dmaicProfile.improvements.feature',
            'dmaicProfile.improvements.rootCause',
            'dmaicProfile.controlPlans.metrics.alerts',
            'bugs.assignee',
            'sprints',
            'releases',
            'supportTickets.assignee',
        ]);

        $readyForRelease = $deliveryProject->tasks
            ->where('status', 'ready_for_release')
            ->values();
        $openBugs = $deliveryProject->bugs
            ->whereNotIn('status', ['resolved', 'closed'])
            ->values();
        $openSupport = $deliveryProject->supportTickets
            ->whereNotIn('status', ['resolved', 'closed', 'converted'])
            ->values();
        $activeSprint = $deliveryProject->sprints->firstWhere('status', 'active');
        $user = request()->user();
        $profile = $deliveryProject->dmaicProfile;
        $schedule = $this->scheduleTimeline->forProject($deliveryProject);

        // Schedule rollup updates rolled_* in DB; sync attrs without wiping eager loads.
        $rolled = DeliveryProject::query()->whereKey($deliveryProject->id)->first(['rolled_start_date', 'rolled_due_date']);
        if ($rolled) {
            $deliveryProject->rolled_start_date = $rolled->rolled_start_date;
            $deliveryProject->rolled_due_date = $rolled->rolled_due_date;
        }

        $expectations = $this->milestoneSync->expectationsFor($deliveryProject);

        $initiativeSystemIds = $deliveryProject->initiative?->systems->pluck('id') ?? collect();
        $assignableFeatures = \App\Models\Feature::query()
            ->with(['system:id,name', 'epic:id,title'])
            ->whereNull('delivery_project_id')
            ->when(
                $initiativeSystemIds->isNotEmpty(),
                fn ($q) => $q->whereIn('system_id', $initiativeSystemIds),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->orderBy('title')
            ->get(['id', 'title', 'system_id', 'epic_id', 'status', 'priority']);

        $planEpics = $deliveryProject->planEpicsQuery()
            ->with(['system:id,name'])
            ->get();

        return Inertia::render('DeliveryProjects/Show', [
            'deliveryProject' => $deliveryProject,
            'expectations' => $expectations['items'],
            'expectationMovement' => $expectations['movement'],
            'assignableFeatures' => $assignableFeatures,
            'planEpics' => $planEpics,
            'canManage' => $user->can('delivery_projects.manage'),
            'canViewDmaic' => $user->can('dmaic.view'),
            'canManageDmaic' => $user->can('dmaic.manage'),
            'canApproveGate' => $profile ? $user->can('approveGate', $profile) : false,
            'canManageReleases' => $user->can('releases.manage'),
            'canManageSprints' => $user->can('sprints.manage'),
            'canManageEpics' => $user->can('epics.manage'),
            'canManageFeatures' => $user->can('features.manage'),
            'canManageTasks' => $user->can('tasks.manage'),
            'canManageKpis' => $user->can('kpis.manage'),
            'canManageProblems' => $user->can('business_problems.manage'),
            'canViewBugs' => $user->can('bugs.view'),
            'canViewSupport' => $user->can('support.view'),
            'improvementFeatures' => $deliveryProject->features->map(fn ($f) => ['id' => $f->id, 'title' => $f->title])->values(),
            'hub' => [
                'ready_for_release_count' => $readyForRelease->count(),
                'open_bugs_count' => $openBugs->count(),
                'open_support_count' => $openSupport->count(),
                'active_sprint' => $activeSprint,
                'features_count' => $deliveryProject->features->count(),
                'tasks_count' => $deliveryProject->tasks->count(),
                'epics_count' => $deliveryProject->features->pluck('epic_id')->filter()->unique()->count(),
            ],
            'nextSteps' => $this->buildNextSteps($deliveryProject, $readyForRelease, $openBugs),
            'schedule' => $schedule,
            'board' => $this->taskBoardService->groupIntoColumns(
                $deliveryProject->tasks->whereNull('parent_id')->values()
            ),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Task>  $readyForRelease
     * @param  \Illuminate\Support\Collection<int, \App\Models\Bug>  $openBugs
     * @return list<array{key: string, title: string, detail: string, href: string|null, action: string|null, tab: string}>
     */
    private function buildNextSteps(DeliveryProject $deliveryProject, $readyForRelease, $openBugs): array
    {
        $steps = [];
        $user = request()->user();

        if ($deliveryProject->type === 'dmaic') {
            if (! $deliveryProject->dmaicProfile && $user->can('dmaic.manage')) {
                $steps[] = [
                    'key' => 'start_improve',
                    'title' => 'Start improvement workspace',
                    'detail' => 'This project is a structured improvement cycle. Initialize the stages to continue.',
                    'href' => null,
                    'action' => 'bootstrap_dmaic',
                    'tab' => 'improve',
                ];
            } elseif ($deliveryProject->dmaicProfile && $deliveryProject->dmaicProfile->current_phase !== 'CLOSED') {
                $phase = $deliveryProject->dmaicProfile->current_phase;
                $steps[] = [
                    'key' => 'continue_improve',
                    'title' => 'Continue improvement cycle',
                    'detail' => 'Current stage needs attention before the outcome is locked in.',
                    'href' => null,
                    'action' => null,
                    'tab' => 'improve',
                    'phase' => $phase,
                ];
            }
        }

        if ($readyForRelease->isNotEmpty() && $user->can('releases.manage')) {
            $steps[] = [
                'key' => 'ship',
                'title' => $readyForRelease->count().' task(s) ready to ship',
                'detail' => 'Create or update a release from ready items.',
                'href' => route('releases.create', ['delivery_project_id' => $deliveryProject->id]),
                'action' => null,
                'tab' => 'ship',
            ];
        }

        if ($deliveryProject->features->isEmpty() && $user->can('features.manage')) {
            $steps[] = [
                'key' => 'plan_feature',
                'title' => 'Assign a feature',
                'detail' => 'Pull shippable features from initiative systems into this project plan.',
                'href' => null,
                'action' => null,
                'tab' => 'plan',
            ];
        }

        if ($deliveryProject->tasks->isEmpty() && $user->can('tasks.manage')) {
            $steps[] = [
                'key' => 'add_task',
                'title' => 'Add the first task',
                'detail' => 'Create engineering work under a Plan feature when possible.',
                'href' => route('tasks.create', ['delivery_project_id' => $deliveryProject->id, 'return_tab' => 'build']),
                'action' => null,
                'tab' => 'build',
            ];
        }

        if ($openBugs->isNotEmpty() && $user->can('bugs.view')) {
            $steps[] = [
                'key' => 'bugs',
                'title' => $openBugs->count().' open bug(s)',
                'detail' => 'Review defects linked to this delivery project.',
                'href' => null,
                'action' => null,
                'tab' => 'quality',
            ];
        }

        if ($deliveryProject->kpis->isNotEmpty() && $user->can('kpis.manage')) {
            $kpi = $deliveryProject->kpis->first();
            $steps[] = [
                'key' => 'kpi',
                'title' => 'Update KPI outcome',
                'detail' => $kpi->name.' — current '.($kpi->current_value ?? '—').' / target '.($kpi->target ?? '—'),
                'href' => route('kpis.show', $kpi),
                'action' => null,
                'tab' => 'overview',
            ];
        }

        if ($steps === []) {
            $steps[] = [
                'key' => 'ok',
                'title' => 'Project is on track',
                'detail' => 'Use the tabs below to plan, build, ship, or improve without leaving this project.',
                'href' => null,
                'action' => null,
                'tab' => 'overview',
            ];
        }

        return array_slice($steps, 0, 3);
    }

    public function edit(DeliveryProject $deliveryProject): Response
    {
        $this->authorize('update', $deliveryProject);

        $deliveryProject->load(['kpis', 'members', 'initiative.systems']);

        return Inertia::render('DeliveryProjects/Form', [
            'deliveryProject' => $deliveryProject,
            'initiatives' => Initiative::with('systems:id,name')->orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'teams' => Team::orderBy('name')->get(['id', 'name']),
            'kpis' => Kpi::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, DeliveryProject $deliveryProject): RedirectResponse
    {
        $this->authorize('update', $deliveryProject);

        $data = $this->validated($request);
        $kpiIds = $data['kpi_ids'] ?? [];
        $memberIds = $data['member_ids'] ?? [];
        unset($data['kpi_ids'], $data['member_ids']);

        $deliveryProject->update($data);
        $deliveryProject->kpis()->sync($kpiIds);
        $deliveryProject->members()->sync($memberIds);
        $this->healthService->refresh($deliveryProject);

        if ($deliveryProject->type === 'dmaic' && ! $deliveryProject->dmaicProfile) {
            $this->dmaicWorkflow->bootstrap($deliveryProject->fresh(), null, $request->user());
        }

        return redirect()->route('delivery-projects.show', $deliveryProject)->with('success', 'Delivery project updated.');
    }

    public function destroy(DeliveryProject $deliveryProject): RedirectResponse
    {
        $this->authorize('delete', $deliveryProject);
        $deliveryProject->delete();

        return redirect()->route('delivery-projects.index')->with('success', 'Delivery project deleted.');
    }

    public function storeMilestone(Request $request, DeliveryProject $deliveryProject): RedirectResponse
    {
        $this->authorize('update', $deliveryProject);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $data['source_type'] = MilestoneSyncService::SOURCE_MANUAL;
        $data['source_id'] = null;
        $data['status'] = $data['status'] ?? 'pending';

        $deliveryProject->milestones()->create($data);

        return redirect()->back()->with('success', 'Custom expectation added.');
    }

    public function updateMilestone(Request $request, DeliveryProject $deliveryProject, DeliveryProjectMilestone $milestone): RedirectResponse
    {
        $this->authorize('update', $deliveryProject);
        abort_unless($milestone->delivery_project_id === $deliveryProject->id, 404);
        abort_if($milestone->isAuto(), 422, 'Edit the linked epic or release instead.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $milestone->update($data);

        return redirect()->back()->with('success', 'Expectation updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['nullable', 'string', 'in:'.implode(',', DeliveryProject::TYPES)],
            'initiative_id' => ['required', 'exists:initiatives,id'],
            'name' => ['required', 'string', 'max:255'],
            'project_owner_id' => ['nullable', 'exists:users,id'],
            'product_owner_id' => ['nullable', 'exists:users,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'start_date' => ['nullable', 'date'],
            'target_completion' => ['nullable', 'date'],
            'actual_completion' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'success_criteria' => ['nullable', 'string'],
            'risks' => ['nullable', 'string'],
            'dependencies' => ['nullable', 'string'],
            'kpi_ids' => ['nullable', 'array'],
            'kpi_ids.*' => ['uuid', 'exists:kpis,id'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['uuid', 'exists:users,id'],
        ]);
    }
}

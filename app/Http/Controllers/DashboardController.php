<?php

namespace App\Http\Controllers;

use App\Models\Bug;
use App\Models\DeliveryProject;
use App\Models\Initiative;
use App\Models\Kpi;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\DeliveryMetricsService;
use App\Services\DeveloperKpiService;
use App\Services\TaskBoardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DeliveryMetricsService $metricsService,
        private DeveloperKpiService $developerKpiService,
        private TaskBoardService $taskBoardService,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Role-primary homes (hub-first UX) before raw permission checks.
        if ($user->hasRole('project_manager') && ! $user->hasAnyRole(['admin', 'executive']) && $user->can('dashboards.team')) {
            return redirect()->route('dashboards.team');
        }

        if ($user->hasRole('improvement_lead') && ! $user->hasAnyRole(['admin', 'executive', 'project_manager']) && $user->can('dashboards.dmaic')) {
            return redirect()->route('dashboards.dmaic');
        }

        if ($user->hasRole('product_owner') && ! $user->hasAnyRole(['admin', 'executive']) && $user->can('pillars.view')) {
            return redirect()->route('portfolio.index');
        }

        if ($user->hasRole('data_analyst') && ! $user->hasAnyRole(['admin', 'executive', 'project_manager', 'improvement_lead']) && $user->can('dashboards.team')) {
            return redirect()->route('dashboards.team');
        }

        if ($user->can('dashboards.executive')) {
            return redirect()->route('dashboards.executive');
        }

        if ($user->can('dashboards.developer') && ! $user->can('dashboards.team') && ! $user->can('dashboards.dmaic')) {
            return redirect()->route('dashboards.developer');
        }

        if ($user->can('dashboards.dmaic') && ! $user->can('dashboards.executive')) {
            return redirect()->route('dashboards.dmaic');
        }

        if ($user->can('dashboards.team')) {
            return redirect()->route('dashboards.team');
        }

        if ($user->can('dashboards.developer')) {
            return redirect()->route('dashboards.developer');
        }

        if ($user->can('delivery_projects.view')) {
            return redirect()->route('delivery-projects.index');
        }

        return Inertia::render('Dashboard', [
            'message' => 'You are logged in. Ask an administrator to assign a role for dashboards.',
        ]);
    }

    public function executive(Request $request): Response
    {
        abort_unless($request->user()->can('dashboards.executive'), 403);

        $initiatives = Initiative::with('strategicPillar')->withCount('deliveryProjects')->latest()->limit(10)->get();
        $deliveryProjects = DeliveryProject::with('initiative')->latest()->limit(15)->get();
        $delayedDeliveryProjects = DeliveryProject::query()
            ->whereNotNull('target_completion')
            ->whereDate('target_completion', '<', now())
            ->whereNotIn('status', ['completed', 'done'])
            ->with('initiative')
            ->get();
        $blockedTasks = Task::query()
            ->where(function ($q) {
                $q->where('status', 'blocked')->orWhereNotNull('blockers');
            })
            ->whereNotIn('status', ['done', 'released'])
            ->with(['deliveryProject', 'assignee'])
            ->limit(20)
            ->get();
        $kpis = Kpi::with('owner')->orderBy('name')->limit(12)->get();

        return Inertia::render('Dashboard/Executive', [
            'initiatives' => $initiatives,
            'deliveryProjects' => $deliveryProjects,
            'delayedDeliveryProjects' => $delayedDeliveryProjects,
            'blockedTasks' => $blockedTasks,
            'kpis' => $kpis,
            'stats' => [
                'initiatives' => Initiative::count(),
                'delivery_projects_on_track' => DeliveryProject::where('health', 'green')->count(),
                'delivery_projects_at_risk' => DeliveryProject::whereIn('health', ['amber', 'red'])->count(),
                'open_tasks' => Task::whereNotIn('status', ['done', 'released'])->count(),
                'blocked_tasks' => Task::where('status', 'blocked')->orWhereNotNull('blockers')->count(),
            ],
        ]);
    }

    public function developer(Request $request): Response
    {
        abort_unless($request->user()->can('dashboards.developer'), 403);

        $user = $request->user();
        $userId = $user->id;

        $from = $request->filled('from')
            ? now()->parse($request->string('from'))->startOfDay()
            : now()->subDays(30)->startOfDay();
        $to = $request->filled('to')
            ? now()->parse($request->string('to'))->endOfDay()
            : now()->endOfDay();

        $kpis = $this->developerKpiService->summarize($user, $from, $to);

        $view = $request->input('view', 'board') === 'list' ? 'list' : 'board';

        $assigned = Task::query()
            ->where('assignee_id', $userId)
            ->whereNotIn('status', ['done', 'released'])
            ->with('deliveryProject')
            ->orderBy('due_date')
            ->get();

        $recentDone = Task::query()
            ->where('assignee_id', $userId)
            ->whereIn('status', Task::BOARD_DONE_STATUSES)
            ->where(function ($q) {
                $q->where('completed_at', '>=', now()->subDays(Task::BOARD_DONE_DAYS))
                    ->orWhere(function ($inner) {
                        $inner->whereNull('completed_at')
                            ->where('status', 'ready_for_release')
                            ->where('updated_at', '>=', now()->subDays(Task::BOARD_DONE_DAYS));
                    });
            })
            ->with('deliveryProject')
            ->orderByDesc('completed_at')
            ->get();

        $boardTasks = $assigned
            ->merge($recentDone)
            ->unique('id')
            ->values();

        $board = collect(Task::BOARD_COLUMNS)->map(function (array $column) use ($boardTasks) {
            $tasks = $boardTasks
                ->filter(fn (Task $t) => in_array($t->status, $column['statuses'], true))
                ->values()
                ->all();

            return [
                'id' => $column['id'],
                'label' => $column['label'],
                'statuses' => $column['statuses'],
                'tasks' => $tasks,
            ];
        })->all();

        $today = $assigned->filter(fn (Task $t) => $t->due_date && $t->due_date->isToday());
        $overdue = $assigned->filter(fn (Task $t) => $t->isOverdue());
        $blocked = $assigned->filter(fn (Task $t) => $t->status === 'blocked' || filled($t->blockers));
        $missingEstimate = $assigned->filter(fn (Task $t) => $t->estimate === null || (float) $t->estimate <= 0);
        $missingDueDate = $assigned->filter(fn (Task $t) => ! $t->due_date);
        $inProgressCount = $assigned->where('status', 'in_progress')->count();

        $openBugs = Bug::query()
            ->where('assignee_id', $userId)
            ->whereNotIn('status', ['closed'])
            ->with('deliveryProject')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Dashboard/Developer', [
            'assigned' => $assigned->values(),
            'today' => $today->values(),
            'overdue' => $overdue->values(),
            'blocked' => $blocked->values(),
            'missingEstimate' => $missingEstimate->values(),
            'missingDueDate' => $missingDueDate->values(),
            'openBugs' => $openBugs,
            'kpis' => $kpis,
            'board' => $board,
            'boardColumns' => Task::BOARD_COLUMNS,
            'wipLimit' => 2,
            'inProgressCount' => $inProgressCount,
            'view' => $view,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'view' => $view,
            ],
        ]);
    }

    public function team(Request $request): Response
    {
        abort_unless($request->user()->can('dashboards.team'), 403);

        $team = $request->filled('team_id')
            ? Team::find($request->input('team_id'))
            : null;

        $user = $request->filled('user_id')
            ? User::find($request->input('user_id'))
            : null;

        $projectId = $request->input('delivery_project_id') ?: null;
        $view = $request->input('view', 'board') === 'metrics' ? 'metrics' : 'board';

        $from = $request->filled('from') ? now()->parse($request->string('from'))->startOfDay() : null;
        $to = $request->filled('to') ? now()->parse($request->string('to'))->endOfDay() : null;

        $metrics = $this->metricsService->summarize($team, $user, $from, $to);

        $kpiFrom = $from ?? now()->parse($metrics['filters']['from'])->startOfDay();
        $kpiTo = $to ?? now()->parse($metrics['filters']['to'])->endOfDay();
        $developerKpis = $user
            ? $this->developerKpiService->summarize($user, $kpiFrom, $kpiTo)
            : null;

        $assigneeIds = null;
        if ($user) {
            $assigneeIds = [$user->id];
        } elseif ($team) {
            $assigneeIds = $team->users()->pluck('users.id')->all();
        }

        $boardTasks = $this->taskBoardService->loadBoardTasks($assigneeIds, $projectId);
        $board = $this->taskBoardService->groupIntoColumns($boardTasks);
        $openForAttention = $boardTasks->filter(
            fn (Task $t) => ! in_array($t->status, ['done', 'released'], true)
        );
        $attention = $this->taskBoardService->attention($openForAttention, 2);

        $recentBugs = Bug::query()
            ->with(['assignee', 'deliveryProject'])
            ->latest()
            ->limit(10)
            ->get();

        $overdueTasks = Task::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->whereNotIn('status', ['done', 'released'])
            ->when($assigneeIds !== null, function ($q) use ($assigneeIds) {
                if ($assigneeIds === []) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn('assignee_id', $assigneeIds);
                }
            })
            ->when($projectId, fn ($q) => $q->where('delivery_project_id', $projectId))
            ->with(['assignee', 'deliveryProject'])
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return Inertia::render('Dashboard/Team', [
            'metrics' => $metrics,
            'developerKpis' => $developerKpis,
            'teams' => Team::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'team_id' => $team?->id,
                'user_id' => $user?->id,
                'delivery_project_id' => $projectId,
                'from' => $metrics['filters']['from'],
                'to' => $metrics['filters']['to'],
                'view' => $view,
            ],
            'view' => $view,
            'board' => $board,
            'attention' => $attention,
            'wipLimit' => 2,
            'recentBugs' => $recentBugs,
            'overdueTasks' => $overdueTasks,
        ]);
    }

    public function dmaic(Request $request): Response
    {
        abort_unless($request->user()->can('dashboards.dmaic'), 403);

        $profiles = \App\Models\Dmaic\DmaicProfile::with(['deliveryProject', 'leader'])
            ->latest()
            ->get();

        $byPhase = $profiles->groupBy('current_phase')->map->count();

        $openAlerts = \App\Models\Dmaic\DmaicControlAlert::query()
            ->with('metric.controlPlan.profile.deliveryProject')
            ->where('status', 'open')
            ->latest()
            ->limit(20)
            ->get();

        $pendingGates = \App\Models\Dmaic\DmaicGate::query()
            ->where('status', 'pending')
            ->whereHas('phase', function ($q) {
                $q->where('status', 'in_progress');
            })
            ->with(['phase.profile.deliveryProject'])
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('Dashboard/Dmaic', [
            'profiles' => $profiles,
            'byPhase' => $byPhase,
            'openAlerts' => $openAlerts,
            'pendingGates' => $pendingGates,
            'stats' => [
                'active' => $profiles->where('status', 'active')->count(),
                'closed' => $profiles->where('status', 'closed')->count(),
                'open_alerts' => $openAlerts->count(),
            ],
        ]);
    }
}

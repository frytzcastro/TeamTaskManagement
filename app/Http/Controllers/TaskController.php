<?php

namespace App\Http\Controllers;

use App\Models\DeliveryProject;
use App\Models\Feature;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\DeliveryMetricsService;
use App\Services\DeliveryProjectHealthService;
use App\Services\ScheduleRollupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    use Concerns\ReturnsToDeliveryProjectHub;

    public function __construct(
        private DeliveryProjectHealthService $healthService,
        private DeliveryMetricsService $metricsService,
        private ScheduleRollupService $scheduleRollup,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Task::class);

        $query = Task::query()
            ->with(['deliveryProject', 'assignee'])
            ->whereNull('parent_id')
            ->latest();

        if ($request->filled('delivery_project_id')) {
            $query->where('delivery_project_id', $request->input('delivery_project_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('sprint_id')) {
            $query->where('sprint_id', $request->input('sprint_id'));
        }

        return Inertia::render('Tasks/Index', [
            'tasks' => $query->paginate(25)->withQueryString(),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'sprints' => Sprint::orderByDesc('start_date')->get(['id', 'name', 'delivery_project_id', 'status']),
            'filters' => $request->only(['delivery_project_id', 'status', 'sprint_id']),
            'statuses' => Task::STATUSES,
            'canManage' => $request->user()->can('tasks.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Task::class);

        $feature = null;
        if ($request->filled('feature_id')) {
            $feature = Feature::query()->whereKey($request->input('feature_id'))->first(['id', 'title', 'delivery_project_id']);
        }

        $defaultProjectId = $request->input('delivery_project_id') ?: $feature?->delivery_project_id;

        $returnFeature = null;
        if ($request->filled('return_feature_id')) {
            $returnFeature = Feature::query()->whereKey($request->input('return_feature_id'))->first(['id', 'title']);
        }

        return Inertia::render('Tasks/Form', [
            'task' => null,
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'features' => Feature::orderBy('title')->get(['id', 'title', 'delivery_project_id']),
            'sprints' => Sprint::orderByDesc('start_date')->get(['id', 'name', 'delivery_project_id', 'status']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'tasks' => Task::orderBy('title')->get(['id', 'title', 'delivery_project_id']),
            'statuses' => Task::STATUSES,
            'defaultDeliveryProjectId' => $defaultProjectId,
            'defaultFeatureId' => $feature?->id,
            'returnFeature' => $returnFeature
                ? ['id' => $returnFeature->id, 'title' => $returnFeature->title]
                : null,
            ...$this->hubReturnProps($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $data = $this->validated($request);
        $dependencyIds = $data['dependency_ids'] ?? [];
        unset($data['dependency_ids']);

        $this->assertEstimateForInProgress(null, $data);

        $task = Task::create($data);
        $this->metricsService->applyTaskTimestamps($task);
        $task->save();
        $task->dependencies()->sync($dependencyIds);
        $this->scheduleRollup->refreshFromTask($task);

        if ($task->assignee_id) {
            User::find($task->assignee_id)?->notify(new TaskAssignedNotification($task));
        }

        $this->healthService->refresh($task->deliveryProject);

        $returnFeatureId = $request->input('return_feature_id');
        if (is_string($returnFeatureId) && $returnFeatureId !== '' && Feature::query()->whereKey($returnFeatureId)->exists()) {
            return redirect()->route('features.show', $returnFeatureId)->with('success', 'Task created.');
        }

        return $this->redirectAfterHubCreate($request, 'tasks.show', $task, 'Task created.');
    }

    public function show(Task $task): Response
    {
        $this->authorize('view', $task);

        $task->load([
            'deliveryProject.initiative',
            'feature.systems',
            'assignee',
            'reviewer',
            'qa',
            'comments.user',
            'dependencies',
            'subtasks.assignee',
            'parent',
        ]);

        return Inertia::render('Tasks/Show', [
            'task' => $task,
            'statuses' => Task::STATUSES,
            'canUpdate' => request()->user()->can('update', $task),
            'canManage' => request()->user()->can('tasks.manage'),
        ]);
    }

    public function edit(Task $task): Response
    {
        $this->authorize('update', $task);

        $task->load('dependencies');

        return Inertia::render('Tasks/Form', [
            'task' => $task,
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'features' => Feature::orderBy('title')->get(['id', 'title', 'delivery_project_id']),
            'sprints' => Sprint::orderByDesc('start_date')->get(['id', 'name', 'delivery_project_id', 'status']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'tasks' => Task::where('id', '!=', $task->id)->orderBy('title')->get(['id', 'title', 'delivery_project_id']),
            'statuses' => Task::STATUSES,
            'defaultDeliveryProjectId' => null,
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $previousAssignee = $task->assignee_id;
        $previousStatus = $task->status;
        $data = $this->validated($request, $task);
        $dependencyIds = $data['dependency_ids'] ?? [];
        unset($data['dependency_ids']);

        if (! $request->user()->can('tasks.manage')) {
            $data = collect($data)->only([
                'status',
                'estimate',
                'start_date',
                'due_date',
                'actual_effort',
                'completion_percent',
                'blockers',
                'git_reference',
                'description',
            ])->all();
        }

        $this->assertEstimateForInProgress($task, $data);

        $task->fill($data);
        if ($request->user()->can('tasks.manage')) {
            $task->dependencies()->sync($dependencyIds);
        }

        $this->metricsService->applyTaskTimestamps($task, $previousStatus);
        $task->save();
        $this->scheduleRollup->refreshFromTask($task->fresh(['feature']));

        if (($data['assignee_id'] ?? null) && ($data['assignee_id'] != $previousAssignee)) {
            User::find($data['assignee_id'])?->notify(new TaskAssignedNotification($task->fresh()));
        }

        $this->healthService->refresh($task->deliveryProject);

        return redirect()->route('tasks.show', $task)->with('success', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $task->loadMissing('feature');
        $deliveryProject = $task->deliveryProject;
        $featureId = $task->feature_id;
        $epicId = $task->epic_id ?: $task->feature?->epic_id;
        $projectId = $task->delivery_project_id;
        $task->delete();

        if ($featureId) {
            $this->scheduleRollup->refreshFeature($featureId);
        } elseif ($epicId) {
            $this->scheduleRollup->refreshEpic($epicId);
        } elseif ($projectId) {
            $this->scheduleRollup->refreshProject($projectId);
        }

        $this->healthService->refresh($deliveryProject);

        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }

    public function storeComment(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('view', $task);

        $data = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return redirect()->back()->with('success', 'Comment added.');
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', Task::STATUSES)],
            'estimate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $previousStatus = $task->status;
        $this->assertEstimateForInProgress($task, $data);

        if (array_key_exists('estimate', $data) && $data['estimate'] !== null) {
            $task->estimate = $data['estimate'];
        }
        $task->status = $data['status'];

        $this->metricsService->applyTaskTimestamps($task, $previousStatus);
        $task->save();
        $this->scheduleRollup->refreshFromTask($task->fresh(['feature']));
        $this->healthService->refresh($task->deliveryProject);

        $task->load('deliveryProject');

        return response()->json([
            'task' => $task,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Task $task = null): array
    {
        return $request->validate([
            'delivery_project_id' => [$task ? 'sometimes' : 'required', 'exists:delivery_projects,id'],
            'feature_id' => ['nullable', 'exists:features,id'],
            'epic_id' => ['nullable', 'exists:epics,id'],
            'sprint_id' => ['nullable', 'exists:sprints,id'],
            'parent_id' => ['nullable', 'exists:tasks,id'],
            'title' => [$task && ! $request->user()->can('tasks.manage') ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
            'qa_id' => ['nullable', 'exists:users,id'],
            'priority' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:'.implode(',', Task::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'estimate' => ['nullable', 'numeric', 'min:0'],
            'actual_effort' => ['nullable', 'numeric', 'min:0'],
            'completion_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'blockers' => ['nullable', 'string'],
            'git_reference' => ['nullable', 'string', 'max:255'],
            'dependency_ids' => ['nullable', 'array'],
            'dependency_ids.*' => ['uuid', 'exists:tasks,id'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertEstimateForInProgress(?Task $task, array $data): void
    {
        $status = $data['status'] ?? $task?->status;
        if ($status !== 'in_progress') {
            return;
        }

        $estimate = array_key_exists('estimate', $data) ? $data['estimate'] : $task?->estimate;
        if ($estimate === null || $estimate === '' || (float) $estimate <= 0) {
            throw ValidationException::withMessages([
                'estimate' => 'Commitment estimate (working days) is required before moving a task to in progress.',
            ]);
        }
    }
}

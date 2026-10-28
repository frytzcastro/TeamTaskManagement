<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\DeliveryMetricsService;
use App\Services\DeliveryProjectHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private DeliveryProjectHealthService $healthService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $query = Task::query()->with(['deliveryProject', 'assignee'])->whereNull('parent_id');

        if ($request->filled('delivery_project_id')) {
            $query->where('delivery_project_id', $request->input('delivery_project_id'));
        }

        return response()->json(['data' => $query->latest()->paginate(50)]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        $data = $request->validate([
            'delivery_project_id' => ['required', 'exists:delivery_projects,id'],
            'feature_id' => ['nullable', 'exists:features,id'],
            'parent_id' => ['nullable', 'exists:tasks,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'priority' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:'.implode(',', Task::STATUSES)],
            'due_date' => ['nullable', 'date'],
            'estimate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $task = Task::create($data);

        if ($task->assignee_id) {
            User::find($task->assignee_id)?->notify(new TaskAssignedNotification($task));
        }

        $this->healthService->refresh($task->deliveryProject);

        return response()->json(['data' => $task], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json([
            'data' => $task->load(['deliveryProject', 'assignee', 'comments.user', 'dependencies', 'subtasks']),
        ]);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'priority' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:'.implode(',', Task::STATUSES)],
            'due_date' => ['nullable', 'date'],
            'estimate' => ['nullable', 'numeric', 'min:0'],
            'actual_effort' => ['nullable', 'numeric', 'min:0'],
            'completion_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'blockers' => ['nullable', 'string'],
            'git_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $previousAssignee = $task->assignee_id;
        $previousStatus = $task->status;

        if (! $request->user()->can('tasks.manage')) {
            $data = collect($data)->only([
                'status', 'actual_effort', 'completion_percent', 'blockers', 'git_reference', 'description',
            ])->all();
        }

        $task->fill($data);
        app(DeliveryMetricsService::class)->applyTaskTimestamps($task, $previousStatus);
        $task->save();

        if (($data['assignee_id'] ?? null) && $data['assignee_id'] != $previousAssignee) {
            User::find($data['assignee_id'])?->notify(new TaskAssignedNotification($task->fresh()));
        }

        $this->healthService->refresh($task->deliveryProject);

        return response()->json(['data' => $task]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $deliveryProject = $task->deliveryProject;
        $task->delete();
        $this->healthService->refresh($deliveryProject);

        return response()->json(null, 204);
    }
}

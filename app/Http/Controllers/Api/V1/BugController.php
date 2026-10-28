<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BugController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Bug::class);

        $query = Bug::query()->with(['assignee', 'deliveryProject'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->paginate(50)]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Bug::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'steps_to_reproduce' => ['nullable', 'string'],
            'expected_result' => ['nullable', 'string'],
            'actual_result' => ['nullable', 'string'],
            'environment' => ['nullable', 'string', 'in:'.implode(',', Bug::ENVIRONMENTS)],
            'severity' => ['nullable', 'string', 'in:'.implode(',', Bug::SEVERITIES)],
            'status' => ['nullable', 'string', 'in:'.implode(',', Bug::STATUSES)],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'delivery_project_id' => ['nullable', 'exists:delivery_projects,id'],
            'feature_id' => ['nullable', 'exists:features,id'],
            'system_id' => ['nullable', 'exists:systems,id'],
            'task_id' => ['nullable', 'exists:tasks,id'],
        ]);

        $data['reporter_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'reported';

        $bug = new Bug($data);
        $bug->applyStatusTimestamps();
        $bug->save();

        return response()->json(['data' => $bug], 201);
    }

    public function show(Bug $bug): JsonResponse
    {
        $this->authorize('view', $bug);

        return response()->json([
            'data' => $bug->load(['reporter', 'assignee', 'deliveryProject', 'feature', 'system', 'task']),
        ]);
    }

    public function update(Request $request, Bug $bug): JsonResponse
    {
        $this->authorize('update', $bug);

        $previousStatus = $bug->status;
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'steps_to_reproduce' => ['nullable', 'string'],
            'expected_result' => ['nullable', 'string'],
            'actual_result' => ['nullable', 'string'],
            'environment' => ['nullable', 'string', 'in:'.implode(',', Bug::ENVIRONMENTS)],
            'severity' => ['nullable', 'string', 'in:'.implode(',', Bug::SEVERITIES)],
            'status' => ['nullable', 'string', 'in:'.implode(',', Bug::STATUSES)],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'delivery_project_id' => ['nullable', 'exists:delivery_projects,id'],
            'feature_id' => ['nullable', 'exists:features,id'],
            'system_id' => ['nullable', 'exists:systems,id'],
            'task_id' => ['nullable', 'exists:tasks,id'],
        ]);

        if (! $request->user()->can('bugs.manage')) {
            $data = collect($data)->only([
                'status',
                'description',
                'steps_to_reproduce',
                'expected_result',
                'actual_result',
                'severity',
                'environment',
            ])->all();
        }

        $bug->fill($data);
        $bug->applyStatusTimestamps($previousStatus);
        $bug->save();

        return response()->json(['data' => $bug->fresh()]);
    }

    public function destroy(Bug $bug): JsonResponse
    {
        $this->authorize('delete', $bug);
        $bug->delete();

        return response()->json(null, 204);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryProject;
use App\Services\DeliveryProjectHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryProjectController extends Controller
{
    public function __construct(private DeliveryProjectHealthService $healthService) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', DeliveryProject::class);

        return response()->json([
            'data' => DeliveryProject::with(['initiative.systems', 'projectOwner', 'features.system'])->withCount('tasks')->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', DeliveryProject::class);

        $data = $request->validate([
            'initiative_id' => ['required', 'exists:initiatives,id'],
            'name' => ['required', 'string', 'max:255'],
            'project_owner_id' => ['nullable', 'exists:users,id'],
            'product_owner_id' => ['nullable', 'exists:users,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'start_date' => ['nullable', 'date'],
            'target_completion' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'success_criteria' => ['nullable', 'string'],
        ]);

        $deliveryProject = DeliveryProject::create($data);
        $this->healthService->refresh($deliveryProject);

        return response()->json(['data' => $deliveryProject->load('features')], 201);
    }

    public function show(DeliveryProject $deliveryProject): JsonResponse
    {
        $this->authorize('view', $deliveryProject);

        return response()->json([
            'data' => $deliveryProject->load(['initiative.systems', 'milestones', 'tasks', 'kpis', 'members', 'features.system', 'features.epic']),
        ]);
    }

    public function update(Request $request, DeliveryProject $deliveryProject): JsonResponse
    {
        $this->authorize('update', $deliveryProject);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'initiative_id' => ['sometimes', 'exists:initiatives,id'],
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
        ]);

        $deliveryProject->update($data);
        $this->healthService->refresh($deliveryProject);

        return response()->json(['data' => $deliveryProject]);
    }

    public function destroy(DeliveryProject $deliveryProject): JsonResponse
    {
        $this->authorize('delete', $deliveryProject);
        $deliveryProject->delete();

        return response()->json(null, 204);
    }
}

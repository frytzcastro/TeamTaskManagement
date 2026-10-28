<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Initiative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InitiativeController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Initiative::class);

        return response()->json([
            'data' => Initiative::with(['strategicPillar', 'owner', 'kpis'])->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Initiative::class);

        $data = $request->validate([
            'strategic_pillar_id' => ['required', 'exists:strategic_pillars,id'],
            'name' => ['required', 'string', 'max:255'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'business_goal' => ['nullable', 'string'],
            'problem' => ['nullable', 'string'],
            'opportunity' => ['nullable', 'string'],
            'expected_business_impact' => ['nullable', 'string'],
            'success_criteria' => ['nullable', 'string'],
            'kpi_ids' => ['nullable', 'array'],
            'kpi_ids.*' => ['uuid', 'exists:kpis,id'],
        ]);

        $kpiIds = $data['kpi_ids'] ?? [];
        unset($data['kpi_ids']);

        $initiative = Initiative::create($data);
        $initiative->kpis()->sync($kpiIds);

        return response()->json(['data' => $initiative->load('kpis')], 201);
    }

    public function show(Initiative $initiative): JsonResponse
    {
        $this->authorize('view', $initiative);

        return response()->json([
            'data' => $initiative->load(['strategicPillar', 'owner', 'deliveryProjects', 'kpis']),
        ]);
    }

    public function update(Request $request, Initiative $initiative): JsonResponse
    {
        $this->authorize('update', $initiative);

        $data = $request->validate([
            'strategic_pillar_id' => ['sometimes', 'exists:strategic_pillars,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'business_goal' => ['nullable', 'string'],
            'problem' => ['nullable', 'string'],
            'opportunity' => ['nullable', 'string'],
            'expected_business_impact' => ['nullable', 'string'],
            'success_criteria' => ['nullable', 'string'],
            'kpi_ids' => ['nullable', 'array'],
            'kpi_ids.*' => ['uuid', 'exists:kpis,id'],
        ]);

        $kpiIds = $data['kpi_ids'] ?? null;
        unset($data['kpi_ids']);

        $initiative->update($data);
        if (is_array($kpiIds)) {
            $initiative->kpis()->sync($kpiIds);
        }

        return response()->json(['data' => $initiative->load('kpis')]);
    }

    public function destroy(Initiative $initiative): JsonResponse
    {
        $this->authorize('delete', $initiative);
        $initiative->delete();

        return response()->json(null, 204);
    }
}

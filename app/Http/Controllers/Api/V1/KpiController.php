<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Kpi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Kpi::class);

        return response()->json([
            'data' => Kpi::with(['owner', 'initiatives', 'deliveryProjects'])->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Kpi::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'definition' => ['nullable', 'string'],
            'formula' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'baseline' => ['nullable', 'numeric'],
            'target' => ['nullable', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'direction' => ['nullable', 'in:lower_is_better,higher_is_better'],
            'unit' => ['nullable', 'string', 'max:50'],
            'initiative_ids' => ['nullable', 'array'],
            'delivery_project_ids' => ['nullable', 'array'],
        ]);

        $initiativeIds = $data['initiative_ids'] ?? [];
        $deliveryProjectIds = $data['delivery_project_ids'] ?? [];
        unset($data['initiative_ids'], $data['delivery_project_ids']);

        $kpi = Kpi::create($data);
        $kpi->initiatives()->sync($initiativeIds);
        $kpi->deliveryProjects()->sync($deliveryProjectIds);

        return response()->json(['data' => $kpi->load(['initiatives', 'deliveryProjects'])], 201);
    }

    public function show(Kpi $kpi): JsonResponse
    {
        $this->authorize('view', $kpi);

        return response()->json([
            'data' => $kpi->load(['owner', 'initiatives', 'deliveryProjects', 'measurements']),
        ]);
    }

    public function update(Request $request, Kpi $kpi): JsonResponse
    {
        $this->authorize('update', $kpi);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'definition' => ['nullable', 'string'],
            'formula' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'baseline' => ['nullable', 'numeric'],
            'target' => ['nullable', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'direction' => ['nullable', 'in:lower_is_better,higher_is_better'],
            'status' => ['nullable', 'string', 'max:50'],
            'trend' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:50'],
            'initiative_ids' => ['nullable', 'array'],
            'delivery_project_ids' => ['nullable', 'array'],
        ]);

        $initiativeIds = $data['initiative_ids'] ?? null;
        $deliveryProjectIds = $data['delivery_project_ids'] ?? null;
        unset($data['initiative_ids'], $data['delivery_project_ids']);

        $kpi->update($data);
        if (is_array($initiativeIds)) {
            $kpi->initiatives()->sync($initiativeIds);
        }
        if (is_array($deliveryProjectIds)) {
            $kpi->deliveryProjects()->sync($deliveryProjectIds);
        }

        return response()->json(['data' => $kpi->load(['initiatives', 'deliveryProjects'])]);
    }

    public function destroy(Kpi $kpi): JsonResponse
    {
        $this->authorize('delete', $kpi);
        $kpi->delete();

        return response()->json(null, 204);
    }
}

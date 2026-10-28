<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StrategicPillar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StrategicPillarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StrategicPillar::class);

        return response()->json([
            'data' => StrategicPillar::withCount('initiatives')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', StrategicPillar::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $pillar = StrategicPillar::create($data);

        return response()->json(['data' => $pillar], 201);
    }

    public function show(StrategicPillar $strategicPillar): JsonResponse
    {
        $this->authorize('view', $strategicPillar);

        $strategicPillar->load(['initiatives.deliveryProjects', 'initiatives.kpis']);

        return response()->json(['data' => $strategicPillar]);
    }

    public function update(Request $request, StrategicPillar $strategicPillar): JsonResponse
    {
        $this->authorize('update', $strategicPillar);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $strategicPillar->update($data);

        return response()->json(['data' => $strategicPillar]);
    }

    public function destroy(StrategicPillar $strategicPillar): JsonResponse
    {
        $this->authorize('delete', $strategicPillar);
        $strategicPillar->delete();

        return response()->json(null, 204);
    }
}

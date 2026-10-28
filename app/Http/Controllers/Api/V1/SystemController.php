<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\System;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', System::class);

        return response()->json([
            'data' => System::with(['owner', 'initiatives', 'epics', 'features'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', System::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:app,service,platform'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $system = System::create($data);

        return response()->json(['data' => $system], 201);
    }

    public function show(System $system): JsonResponse
    {
        $this->authorize('view', $system);

        return response()->json([
            'data' => $system->load(['owner', 'initiatives', 'epics', 'features']),
        ]);
    }

    public function update(Request $request, System $system): JsonResponse
    {
        $this->authorize('update', $system);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:app,service,platform'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $system->update($data);

        return response()->json(['data' => $system]);
    }

    public function destroy(System $system): JsonResponse
    {
        $this->authorize('delete', $system);
        $system->delete();

        return response()->json(null, 204);
    }
}

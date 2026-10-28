<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use App\Models\Kpi;
use App\Models\StrategicPillar;
use App\Models\System;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InitiativeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Initiative::class);

        return Inertia::render('Initiatives/Index', [
            'initiatives' => Initiative::query()
                ->with(['strategicPillar', 'owner'])
                ->withCount('deliveryProjects')
                ->latest()
                ->get(),
            'canManage' => $request->user()->can('initiatives.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Initiative::class);

        $defaultPillarId = $request->input('strategic_pillar_id') ?: null;
        $returnPillar = null;
        if ($request->boolean('return_to_pillar') && $defaultPillarId) {
            $returnPillar = StrategicPillar::query()->whereKey($defaultPillarId)->first(['id', 'name']);
        }

        return Inertia::render('Initiatives/Form', [
            'initiative' => null,
            'pillars' => StrategicPillar::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'kpis' => Kpi::orderBy('name')->get(['id', 'name']),
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'defaultStrategicPillarId' => $defaultPillarId,
            'returnPillar' => $returnPillar,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Initiative::class);

        $data = $this->validated($request);
        $kpiIds = $data['kpi_ids'] ?? [];
        $systemIds = $data['system_ids'] ?? [];
        unset($data['kpi_ids'], $data['system_ids']);

        $initiative = Initiative::create($data);
        $initiative->kpis()->sync($kpiIds);
        $initiative->systems()->sync($systemIds);

        $returnPillarId = $request->input('return_pillar_id');
        if (
            $returnPillarId
            && (string) $returnPillarId === (string) $initiative->strategic_pillar_id
            && StrategicPillar::query()->whereKey($returnPillarId)->exists()
        ) {
            return redirect()
                ->route('pillars.show', $returnPillarId)
                ->with('success', 'Initiative created.');
        }

        return redirect()->route('initiatives.show', $initiative)->with('success', 'Initiative created.');
    }

    public function show(Initiative $initiative): Response
    {
        $this->authorize('view', $initiative);

        $initiative->load(['strategicPillar', 'owner', 'deliveryProjects.projectOwner', 'kpis', 'systems']);

        return Inertia::render('Initiatives/Show', [
            'initiative' => $initiative,
            'canManage' => request()->user()->can('initiatives.manage'),
        ]);
    }

    public function edit(Initiative $initiative): Response
    {
        $this->authorize('update', $initiative);

        $initiative->load(['kpis', 'systems']);

        return Inertia::render('Initiatives/Form', [
            'initiative' => $initiative,
            'pillars' => StrategicPillar::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'kpis' => Kpi::orderBy('name')->get(['id', 'name']),
            'systems' => System::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Initiative $initiative): RedirectResponse
    {
        $this->authorize('update', $initiative);

        $data = $this->validated($request);
        $kpiIds = $data['kpi_ids'] ?? [];
        $systemIds = $data['system_ids'] ?? [];
        unset($data['kpi_ids'], $data['system_ids']);

        $initiative->update($data);
        $initiative->kpis()->sync($kpiIds);
        $initiative->systems()->sync($systemIds);

        return redirect()->route('initiatives.show', $initiative)->with('success', 'Initiative updated.');
    }

    public function destroy(Initiative $initiative): RedirectResponse
    {
        $this->authorize('delete', $initiative);
        $initiative->delete();

        return redirect()->route('initiatives.index')->with('success', 'Initiative deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
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
            'dependencies' => ['nullable', 'string'],
            'risks' => ['nullable', 'string'],
            'kpi_ids' => ['nullable', 'array'],
            'kpi_ids.*' => ['uuid', 'exists:kpis,id'],
            'system_ids' => ['nullable', 'array'],
            'system_ids.*' => ['uuid', 'exists:systems,id'],
        ]);
    }
}

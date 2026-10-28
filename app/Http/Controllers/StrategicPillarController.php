<?php

namespace App\Http\Controllers;

use App\Models\StrategicPillar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StrategicPillarController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StrategicPillar::class);

        return Inertia::render('Pillars/Index', [
            'pillars' => StrategicPillar::query()
                ->withCount('initiatives')
                ->orderBy('name')
                ->get(),
            'canManage' => $request->user()->can('pillars.manage'),
        ]);
    }

    public function show(StrategicPillar $strategicPillar): Response
    {
        $this->authorize('view', $strategicPillar);

        $strategicPillar->load([
            'initiatives.owner',
            'initiatives.deliveryProjects',
            'initiatives.kpis',
        ]);

        $user = request()->user();

        return Inertia::render('Pillars/Show', [
            'pillar' => $strategicPillar,
            'canManage' => $user->can('pillars.manage'),
            'canManageInitiatives' => $user->can('initiatives.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StrategicPillar::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        StrategicPillar::create($data);

        return redirect()->route('pillars.index')->with('success', 'Strategic pillar created.');
    }

    public function update(Request $request, StrategicPillar $strategicPillar): RedirectResponse
    {
        $this->authorize('update', $strategicPillar);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $strategicPillar->update($data);

        return redirect()->back()->with('success', 'Strategic pillar updated.');
    }

    public function destroy(StrategicPillar $strategicPillar): RedirectResponse
    {
        $this->authorize('delete', $strategicPillar);
        $strategicPillar->delete();

        return redirect()->route('pillars.index')->with('success', 'Strategic pillar deleted.');
    }
}

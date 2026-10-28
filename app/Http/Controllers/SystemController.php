<?php

namespace App\Http\Controllers;

use App\Models\System;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', System::class);

        return Inertia::render('Systems/Index', [
            'systems' => System::with('owner')
                ->withCount(['epics', 'features'])
                ->orderBy('name')
                ->get(),
            'canManage' => $request->user()->can('systems.manage'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', System::class);

        return Inertia::render('Systems/Form', [
            'system' => null,
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', System::class);

        $data = $this->validated($request);
        $system = System::create($data);

        return redirect()->route('systems.show', $system)->with('success', 'System created.');
    }

    public function show(System $system): Response
    {
        $this->authorize('view', $system);

        $system->load([
            'owner',
            'initiatives',
            'epics' => fn ($q) => $q->withCount('features')->orderBy('title'),
            'features' => fn ($q) => $q->with(['epic:id,title', 'deliveryProject:id,name'])->orderBy('title'),
        ]);

        return Inertia::render('Systems/Show', [
            'system' => $system,
            'canManage' => request()->user()->can('systems.manage'),
            'canManageEpics' => request()->user()->can('epics.manage'),
            'canManageFeatures' => request()->user()->can('features.manage'),
        ]);
    }

    public function edit(System $system): Response
    {
        $this->authorize('update', $system);

        return Inertia::render('Systems/Form', [
            'system' => $system,
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, System $system): RedirectResponse
    {
        $this->authorize('update', $system);

        $system->update($this->validated($request));

        return redirect()->route('systems.show', $system)->with('success', 'System updated.');
    }

    public function destroy(System $system): RedirectResponse
    {
        $this->authorize('delete', $system);
        $system->delete();

        return redirect()->route('systems.index')->with('success', 'System deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:app,service,platform'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }
}

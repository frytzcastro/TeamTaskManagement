<?php

namespace App\Http\Controllers;

use App\Models\Epic;
use App\Models\System;
use App\Services\MilestoneSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EpicController extends Controller
{
    public function __construct(
        private MilestoneSyncService $milestoneSync,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Epic::class);

        $query = Epic::query()->with('system')->withCount('features')->latest();

        if ($request->filled('system_id')) {
            $query->where('system_id', $request->input('system_id'));
        }

        return Inertia::render('Epics/Index', [
            'epics' => $query->paginate(25)->withQueryString(),
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['system_id']),
            'canManage' => $request->user()->can('epics.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Epic::class);

        return Inertia::render('Epics/Form', [
            'epic' => null,
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'defaultSystemId' => $request->input('system_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Epic::class);
        $epic = Epic::create($this->validated($request));
        $this->milestoneSync->syncEpic($epic);

        return redirect()->route('epics.show', $epic)->with('success', 'Epic created.');
    }

    public function show(Epic $epic): Response
    {
        $this->authorize('view', $epic);
        $epic->load(['system', 'features.deliveryProject']);

        return Inertia::render('Epics/Show', [
            'epic' => $epic,
            'canManage' => request()->user()->can('epics.manage'),
            'canManageFeatures' => request()->user()->can('features.manage'),
        ]);
    }

    public function edit(Epic $epic): Response
    {
        $this->authorize('update', $epic);

        return Inertia::render('Epics/Form', [
            'epic' => $epic,
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'defaultSystemId' => null,
        ]);
    }

    public function update(Request $request, Epic $epic): RedirectResponse
    {
        $this->authorize('update', $epic);
        $epic->update($this->validated($request));
        $this->milestoneSync->syncEpic($epic->fresh());

        return redirect()->route('epics.show', $epic)->with('success', 'Epic updated.');
    }

    public function destroy(Epic $epic): RedirectResponse
    {
        $this->authorize('delete', $epic);
        $this->milestoneSync->removeEpic($epic);
        $epic->delete();

        return redirect()->route('epics.index')->with('success', 'Epic deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'system_id' => ['required', 'exists:systems,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }
}

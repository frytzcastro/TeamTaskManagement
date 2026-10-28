<?php

namespace App\Http\Controllers;

use App\Models\DeliveryProject;
use App\Models\Epic;
use App\Models\Feature;
use App\Models\System;
use App\Services\MilestoneSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeatureController extends Controller
{
    use Concerns\ReturnsToDeliveryProjectHub;

    public function __construct(
        private MilestoneSyncService $milestoneSync,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Feature::class);

        $query = Feature::query()->with(['system', 'deliveryProject', 'epic'])->latest();

        if ($request->filled('system_id')) {
            $query->where('system_id', $request->input('system_id'));
        }
        if ($request->filled('delivery_project_id')) {
            $query->where('delivery_project_id', $request->input('delivery_project_id'));
        }
        if ($request->boolean('backlog_only')) {
            $query->whereNull('delivery_project_id');
        }

        return Inertia::render('Features/Index', [
            'features' => $query->paginate(25)->withQueryString(),
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['system_id', 'delivery_project_id', 'backlog_only']),
            'canManage' => $request->user()->can('features.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Feature::class);

        $systemId = $request->input('system_id');
        $epicId = $request->input('epic_id');
        if ($epicId && ! $systemId) {
            $systemId = Epic::query()->whereKey($epicId)->value('system_id');
        }

        return Inertia::render('Features/Form', [
            'feature' => null,
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'epics' => Epic::orderBy('title')->get(['id', 'title', 'system_id']),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'defaultSystemId' => $systemId ?: null,
            'defaultEpicId' => $epicId ?: null,
            'defaultDeliveryProjectId' => $request->input('delivery_project_id') ?: null,
            'defaultDodChecklist' => Feature::defaultDodChecklist(),
            ...$this->hubReturnProps($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Feature::class);
        $data = $this->validated($request);
        if (empty($data['dod_checklist'])) {
            $data['dod_checklist'] = Feature::defaultDodChecklist();
        }
        $feature = Feature::create($data);
        if ($feature->epic_id) {
            $this->milestoneSync->syncEpic(Epic::findOrFail($feature->epic_id));
        }

        return $this->redirectAfterHubCreate($request, 'features.show', $feature, 'Feature created.');
    }

    public function show(Feature $feature): Response
    {
        $this->authorize('view', $feature);
        $feature->load(['system', 'deliveryProject', 'epic', 'tasks.assignee']);

        return Inertia::render('Features/Show', [
            'feature' => $feature,
            'canManage' => request()->user()->can('features.manage'),
            'canManageTasks' => request()->user()->can('tasks.manage'),
        ]);
    }

    public function edit(Feature $feature): Response
    {
        $this->authorize('update', $feature);

        return Inertia::render('Features/Form', [
            'feature' => $feature,
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'epics' => Epic::orderBy('title')->get(['id', 'title', 'system_id']),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'defaultSystemId' => null,
            'defaultEpicId' => null,
            'defaultDeliveryProjectId' => null,
            'defaultDodChecklist' => Feature::defaultDodChecklist(),
        ]);
    }

    public function update(Request $request, Feature $feature): RedirectResponse
    {
        $this->authorize('update', $feature);
        $previousEpicId = $feature->epic_id;
        $feature->update($this->validated($request));
        $feature->refresh();

        if ($previousEpicId) {
            $this->milestoneSync->syncEpic(Epic::findOrFail($previousEpicId));
        }
        if ($feature->epic_id && $feature->epic_id !== $previousEpicId) {
            $this->milestoneSync->syncEpic(Epic::findOrFail($feature->epic_id));
        } elseif ($feature->epic_id) {
            $this->milestoneSync->syncEpic(Epic::findOrFail($feature->epic_id));
        }

        return redirect()->route('features.show', $feature)->with('success', 'Feature updated.');
    }

    public function destroy(Feature $feature): RedirectResponse
    {
        $this->authorize('delete', $feature);
        $epicId = $feature->epic_id;
        $feature->delete();
        if ($epicId) {
            $this->milestoneSync->syncEpic(Epic::findOrFail($epicId));
        }

        return redirect()->route('features.index')->with('success', 'Feature deleted.');
    }

    public function assign(Request $request, Feature $feature): RedirectResponse
    {
        $this->authorize('update', $feature);

        $data = $request->validate([
            'delivery_project_id' => ['required', 'exists:delivery_projects,id'],
        ]);

        $project = DeliveryProject::with('initiative.systems')->findOrFail($data['delivery_project_id']);
        $allowed = $project->initiative?->systems->pluck('id') ?? collect();
        abort_unless(
            $allowed->isEmpty() || $allowed->contains($feature->system_id),
            422,
            'Feature system is not linked to this project\'s initiative.'
        );

        $feature->update(['delivery_project_id' => $project->id]);
        if ($feature->epic_id) {
            $this->milestoneSync->syncEpic(Epic::findOrFail($feature->epic_id));
        }

        return back()->with('success', 'Feature assigned to project.');
    }

    public function unassign(Feature $feature): RedirectResponse
    {
        $this->authorize('update', $feature);
        $epicId = $feature->epic_id;
        $feature->update(['delivery_project_id' => null]);
        if ($epicId) {
            $this->milestoneSync->syncEpic(Epic::findOrFail($epicId));
        }

        return back()->with('success', 'Feature returned to system backlog.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'system_id' => ['required', 'exists:systems,id'],
            'delivery_project_id' => ['nullable', 'exists:delivery_projects,id'],
            'epic_id' => ['nullable', 'exists:epics,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'acceptance_criteria' => ['nullable', 'string'],
            'dod_checklist' => ['nullable', 'array'],
            'dod_checklist.*.key' => ['required', 'string'],
            'dod_checklist.*.label' => ['required', 'string'],
            'dod_checklist.*.completed' => ['required', 'boolean'],
            'priority' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        if (! empty($data['epic_id'])) {
            $epicSystemId = Epic::query()->whereKey($data['epic_id'])->value('system_id');
            if ($epicSystemId && $epicSystemId !== $data['system_id']) {
                abort(422, 'Epic must belong to the same system as the feature.');
            }
        }

        return $data;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DeliveryProject;
use App\Models\Sprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SprintController extends Controller
{
    use Concerns\ReturnsToDeliveryProjectHub;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Sprint::class);

        $query = Sprint::query()->with('deliveryProject')->withCount('tasks')->latest();
        if ($request->filled('delivery_project_id')) {
            $query->where('delivery_project_id', $request->input('delivery_project_id'));
        }

        return Inertia::render('Sprints/Index', [
            'sprints' => $query->paginate(25)->withQueryString(),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['delivery_project_id']),
            'canManage' => $request->user()->can('sprints.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Sprint::class);

        return Inertia::render('Sprints/Form', [
            'sprint' => null,
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'statuses' => Sprint::STATUSES,
            'defaultDeliveryProjectId' => $request->input('delivery_project_id') ?: null,
            ...$this->hubReturnProps($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Sprint::class);
        $sprint = Sprint::create($this->validated($request));

        return $this->redirectAfterHubCreate($request, 'sprints.show', $sprint, 'Sprint created.');
    }

    public function show(Sprint $sprint): Response
    {
        $this->authorize('view', $sprint);
        $sprint->load(['deliveryProject', 'tasks.assignee']);

        return Inertia::render('Sprints/Show', [
            'sprint' => $sprint,
            'stats' => [
                'total' => $sprint->tasks->count(),
                'done' => $sprint->tasks->whereIn('status', ['done', 'released'])->count(),
            ],
            'canManage' => request()->user()->can('sprints.manage'),
        ]);
    }

    public function edit(Sprint $sprint): Response
    {
        $this->authorize('update', $sprint);

        return Inertia::render('Sprints/Form', [
            'sprint' => $sprint,
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'statuses' => Sprint::STATUSES,
            'defaultDeliveryProjectId' => null,
        ]);
    }

    public function update(Request $request, Sprint $sprint): RedirectResponse
    {
        $this->authorize('update', $sprint);
        $sprint->update($this->validated($request));

        return redirect()->route('sprints.show', $sprint)->with('success', 'Sprint updated.');
    }

    public function destroy(Sprint $sprint): RedirectResponse
    {
        $this->authorize('delete', $sprint);
        $sprint->delete();

        return redirect()->route('sprints.index')->with('success', 'Sprint deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'delivery_project_id' => ['required', 'exists:delivery_projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', 'in:'.implode(',', Sprint::STATUSES)],
            'goal' => ['nullable', 'string'],
        ]);
    }
}

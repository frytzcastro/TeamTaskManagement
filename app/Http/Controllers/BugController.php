<?php

namespace App\Http\Controllers;

use App\Models\Bug;
use App\Models\DeliveryProject;
use App\Models\Dmaic\DmaicImprovement;
use App\Models\Dmaic\DmaicProfile;
use App\Models\Dmaic\DmaicRootCause;
use App\Models\Feature;
use App\Models\System;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BugController extends Controller
{
    use Concerns\ReturnsToDeliveryProjectHub;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Bug::class);

        $query = Bug::query()
            ->with(['assignee', 'reporter', 'deliveryProject', 'system'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        if ($request->filled('delivery_project_id')) {
            $query->where('delivery_project_id', $request->input('delivery_project_id'));
        }

        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->input('assignee_id'));
        }

        return Inertia::render('Bugs/Index', [
            'bugs' => $query->paginate(25)->withQueryString(),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['status', 'severity', 'delivery_project_id', 'assignee_id']),
            'statuses' => Bug::STATUSES,
            'severities' => Bug::SEVERITIES,
            'canManage' => $request->user()->can('bugs.manage'),
            'canReport' => $request->user()->can('create', Bug::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Bug::class);

        return Inertia::render('Bugs/Form', [
            'bug' => null,
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'features' => Feature::orderBy('title')->get(['id', 'title', 'delivery_project_id']),
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'tasks' => Task::orderBy('title')->get(['id', 'title', 'delivery_project_id']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'dmaicProfiles' => DmaicProfile::with('deliveryProject:id,name')->latest()->get(['id', 'delivery_project_id', 'current_phase']),
            'rootCauses' => DmaicRootCause::orderBy('title')->get(['id', 'title', 'dmaic_profile_id']),
            'improvements' => DmaicImprovement::orderBy('title')->get(['id', 'title', 'dmaic_profile_id']),
            'statuses' => Bug::STATUSES,
            'severities' => Bug::SEVERITIES,
            'environments' => Bug::ENVIRONMENTS,
            'defaults' => [
                'delivery_project_id' => $request->input('delivery_project_id') ?: null,
                'task_id' => $request->input('task_id') ?: null,
                'system_id' => $request->input('system_id') ?: null,
                'feature_id' => $request->input('feature_id') ?: null,
            ],
            ...$this->hubReturnProps($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Bug::class);

        $data = $this->validated($request);
        $data['reporter_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'reported';

        $bug = new Bug($data);
        $bug->applyStatusTimestamps();
        $bug->save();

        return $this->redirectAfterHubCreate($request, 'bugs.show', $bug, 'Bug reported.');
    }

    public function show(Bug $bug): Response
    {
        $this->authorize('view', $bug);

        $bug->load([
            'reporter',
            'assignee',
            'deliveryProject',
            'feature',
            'system',
            'task',
        ]);

        return Inertia::render('Bugs/Show', [
            'bug' => $bug,
            'statuses' => Bug::STATUSES,
            'canUpdate' => request()->user()->can('update', $bug),
            'canManage' => request()->user()->can('bugs.manage'),
        ]);
    }

    public function edit(Bug $bug): Response
    {
        $this->authorize('update', $bug);

        return Inertia::render('Bugs/Form', [
            'bug' => $bug,
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'features' => Feature::orderBy('title')->get(['id', 'title', 'delivery_project_id']),
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'tasks' => Task::orderBy('title')->get(['id', 'title', 'delivery_project_id']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'dmaicProfiles' => DmaicProfile::with('deliveryProject:id,name')->latest()->get(['id', 'delivery_project_id', 'current_phase']),
            'rootCauses' => DmaicRootCause::orderBy('title')->get(['id', 'title', 'dmaic_profile_id']),
            'improvements' => DmaicImprovement::orderBy('title')->get(['id', 'title', 'dmaic_profile_id']),
            'statuses' => Bug::STATUSES,
            'severities' => Bug::SEVERITIES,
            'environments' => Bug::ENVIRONMENTS,
            'defaults' => [],
        ]);
    }

    public function update(Request $request, Bug $bug): RedirectResponse
    {
        $this->authorize('update', $bug);

        $previousStatus = $bug->status;
        $data = $this->validated($request, $bug);

        if (! $request->user()->can('bugs.manage')) {
            $data = collect($data)->only([
                'status',
                'description',
                'steps_to_reproduce',
                'expected_result',
                'actual_result',
                'severity',
                'environment',
            ])->all();
        }

        $bug->fill($data);
        $bug->applyStatusTimestamps($previousStatus);
        $bug->save();

        return redirect()->route('bugs.show', $bug)->with('success', 'Bug updated.');
    }

    public function destroy(Bug $bug): RedirectResponse
    {
        $this->authorize('delete', $bug);
        $bug->delete();

        return redirect()->route('bugs.index')->with('success', 'Bug deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Bug $bug = null): array
    {
        $titleRule = $bug && ! $request->user()->can('bugs.manage') ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$titleRule, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'steps_to_reproduce' => ['nullable', 'string'],
            'expected_result' => ['nullable', 'string'],
            'actual_result' => ['nullable', 'string'],
            'environment' => ['nullable', 'string', 'in:'.implode(',', Bug::ENVIRONMENTS)],
            'severity' => ['nullable', 'string', 'in:'.implode(',', Bug::SEVERITIES)],
            'status' => ['nullable', 'string', 'in:'.implode(',', Bug::STATUSES)],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'delivery_project_id' => ['nullable', 'exists:delivery_projects,id'],
            'feature_id' => ['nullable', 'exists:features,id'],
            'system_id' => ['nullable', 'exists:systems,id'],
            'task_id' => ['nullable', 'exists:tasks,id'],
            'dmaic_profile_id' => ['nullable', 'exists:dmaic_profiles,id'],
            'dmaic_root_cause_id' => ['nullable', 'exists:dmaic_root_causes,id'],
            'dmaic_improvement_id' => ['nullable', 'exists:dmaic_improvements,id'],
        ]);
    }
}

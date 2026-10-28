<?php

namespace App\Http\Controllers;

use App\Models\Bug;
use App\Models\DeliveryProject;
use App\Models\Deployment;
use App\Models\Feature;
use App\Models\System;
use App\Models\Release;
use App\Models\ReleaseItem;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ReleaseApprovedNotification;
use App\Services\DeliveryMetricsService;
use App\Services\MilestoneSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseController extends Controller
{
    use Concerns\ReturnsToDeliveryProjectHub;

    public function __construct(
        private MilestoneSyncService $milestoneSync,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Release::class);

        $query = Release::query()->with(['deliveryProject', 'system'])->latest();
        if ($request->filled('delivery_project_id')) {
            $query->where('delivery_project_id', $request->input('delivery_project_id'));
        }

        return Inertia::render('Releases/Index', [
            'releases' => $query->paginate(25)->withQueryString(),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['delivery_project_id']),
            'canManage' => $request->user()->can('releases.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Release::class);

        return Inertia::render('Releases/Form', [
            'release' => null,
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'statuses' => Release::STATUSES,
            'environments' => Release::ENVIRONMENTS,
            'defaultDeliveryProjectId' => $request->input('delivery_project_id') ?: null,
            ...$this->hubReturnProps($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Release::class);
        $release = Release::create($this->validated($request));
        $this->milestoneSync->syncRelease($release);

        return $this->redirectAfterHubCreate($request, 'releases.show', $release, 'Release created.');
    }

    public function show(Release $release): Response
    {
        $this->authorize('view', $release);
        $release->load(['deliveryProject', 'system', 'approver', 'items', 'deployments.deployer']);

        $items = $release->items->map(function (ReleaseItem $item) {
            $related = $item->resolveRelated();

            return [
                'id' => $item->id,
                'item_type' => $item->item_type,
                'item_id' => $item->item_id,
                'title' => $related?->title ?? $related?->name ?? ('#'.$item->item_id),
                'status' => $related?->status ?? null,
            ];
        });

        $projectId = $release->delivery_project_id;

        return Inertia::render('Releases/Show', [
            'release' => $release,
            'items' => $items,
            'availableTasks' => Task::where('delivery_project_id', $projectId)
                ->whereIn('status', ['ready_for_release', 'qa', 'done'])
                ->orderBy('title')->get(['id', 'title', 'status']),
            'availableFeatures' => Feature::where('delivery_project_id', $projectId)->orderBy('title')->get(['id', 'title', 'status']),
            'availableBugs' => Bug::where('delivery_project_id', $projectId)->orderBy('title')->get(['id', 'title', 'status']),
            'canManage' => request()->user()->can('releases.manage'),
        ]);
    }

    public function edit(Release $release): Response
    {
        $this->authorize('update', $release);

        return Inertia::render('Releases/Form', [
            'release' => $release,
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'statuses' => Release::STATUSES,
            'environments' => Release::ENVIRONMENTS,
            'defaultDeliveryProjectId' => null,
        ]);
    }

    public function update(Request $request, Release $release): RedirectResponse
    {
        $this->authorize('update', $release);
        $previous = $release->status;
        $data = $this->validated($request);
        $release->update($data);
        $this->milestoneSync->syncRelease($release->fresh());

        if (($data['status'] ?? null) === 'approved' && $previous !== 'approved') {
            $release->update(['approved_by' => $request->user()->id]);
            $owners = User::role(['project_manager', 'product_owner', 'qa'])->get();
            foreach ($owners as $user) {
                $user->notify(new ReleaseApprovedNotification($release->fresh()));
            }
        }

        return redirect()->route('releases.show', $release)->with('success', 'Release updated.');
    }

    public function destroy(Release $release): RedirectResponse
    {
        $this->authorize('delete', $release);
        $this->milestoneSync->removeRelease($release);
        $release->delete();

        return redirect()->route('releases.index')->with('success', 'Release deleted.');
    }

    public function storeItem(Request $request, Release $release): RedirectResponse
    {
        $this->authorize('update', $release);
        $data = $request->validate([
            'item_type' => ['required', 'in:'.implode(',', ReleaseItem::TYPES)],
            'item_id' => ['required', 'uuid'],
        ]);

        $release->items()->firstOrCreate($data);

        return redirect()->back()->with('success', 'Item added to release.');
    }

    public function destroyItem(Release $release, ReleaseItem $item): RedirectResponse
    {
        $this->authorize('update', $release);
        abort_unless($item->release_id === $release->id, 404);
        $item->delete();

        return redirect()->back()->with('success', 'Item removed.');
    }

    public function storeDeployment(Request $request, Release $release): RedirectResponse
    {
        $this->authorize('update', $release);
        $data = $request->validate([
            'environment' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:'.implode(',', Deployment::STATUSES)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $deployment = $release->deployments()->create([
            ...$data,
            'deployed_by' => $request->user()->id,
            'deployed_at' => $data['status'] === 'success' ? now() : null,
        ]);

        if ($deployment->status === 'success') {
            $this->syncTasksReleased($release);
            $release->update(['status' => 'deployed', 'released_at' => now()]);
            $this->milestoneSync->syncRelease($release->fresh());
        }

        return redirect()->back()->with('success', 'Deployment logged.');
    }

    public function markDeployed(Request $request, Release $release): RedirectResponse
    {
        $this->authorize('update', $release);

        DB::transaction(function () use ($release, $request) {
            $release->deployments()->create([
                'environment' => $release->environment,
                'status' => 'success',
                'deployed_at' => now(),
                'deployed_by' => $request->user()->id,
                'notes' => 'Marked deployed via release workspace',
            ]);
            $this->syncTasksReleased($release);
            $release->update([
                'status' => 'deployed',
                'released_at' => now(),
                'approved_by' => $release->approved_by ?? $request->user()->id,
            ]);
        });

        $this->milestoneSync->syncRelease($release->fresh());

        return redirect()->back()->with('success', 'Release marked deployed; linked tasks set to released.');
    }

    private function syncTasksReleased(Release $release): void
    {
        $taskIds = $release->items()->where('item_type', 'task')->pluck('item_id');
        $metrics = app(DeliveryMetricsService::class);
        Task::whereIn('id', $taskIds)->get()->each(function (Task $task) use ($metrics) {
            $task->status = 'released';
            $metrics->applyTaskTimestamps($task);
            $task->save();
        });
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'delivery_project_id' => ['required', 'exists:delivery_projects,id'],
            'system_id' => ['nullable', 'exists:systems,id'],
            'name' => ['required', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:50'],
            'environment' => ['nullable', 'string', 'in:'.implode(',', Release::ENVIRONMENTS)],
            'status' => ['nullable', 'string', 'in:'.implode(',', Release::STATUSES)],
            'target_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}

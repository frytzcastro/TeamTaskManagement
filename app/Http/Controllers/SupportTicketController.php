<?php

namespace App\Http\Controllers;

use App\Models\Bug;
use App\Models\DeliveryProject;
use App\Models\Feature;
use App\Models\System;
use App\Models\SupportComment;
use App\Models\SupportSla;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\User;
use App\Notifications\SupportTicketAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SupportTicket::class);

        $query = SupportTicket::query()
            ->with(['assignee', 'reporter', 'system', 'deliveryProject'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }
        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->input('assignee_id'));
        }

        $tickets = $query->paginate(25)->withQueryString();
        $tickets->getCollection()->transform(function (SupportTicket $ticket) {
            $ticket->response_breached = $ticket->isResponseBreached();
            $ticket->resolution_breached = $ticket->isResolutionBreached();

            return $ticket;
        });

        return Inertia::render('Support/Index', [
            'tickets' => $tickets,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['status', 'priority', 'assignee_id']),
            'statuses' => SupportTicket::STATUSES,
            'priorities' => SupportTicket::PRIORITIES,
            'canManage' => $request->user()->can('support.manage'),
            'canCreate' => $request->user()->can('create', SupportTicket::class),
            'canManageSla' => $request->user()->can('support.manage'),
            'slas' => SupportSla::orderBy('id')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SupportTicket::class);

        return Inertia::render('Support/Form', [
            'ticket' => null,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'categories' => SupportTicket::CATEGORIES,
            'priorities' => SupportTicket::PRIORITIES,
            'statuses' => SupportTicket::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SupportTicket::class);
        $data = $this->validated($request);
        $data['reporter_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'open';

        $ticket = new SupportTicket($data);
        $ticket->created_at = now();
        $ticket->applySlaFromPriority();
        $ticket->save();

        if (! empty($data['assignee_id'])) {
            User::find($data['assignee_id'])?->notify(new SupportTicketAssignedNotification($ticket));
        }

        return redirect()->route('support.show', $ticket)->with('success', 'Support ticket created.');
    }

    public function show(SupportTicket $support): Response
    {
        $this->authorize('view', $support);
        $support->load([
            'assignee', 'reporter', 'system', 'deliveryProject',
            'comments.user', 'convertedBug', 'convertedTask', 'convertedFeature',
        ]);
        $support->response_breached = $support->isResponseBreached();
        $support->resolution_breached = $support->isResolutionBreached();

        return Inertia::render('Support/Show', [
            'ticket' => $support,
            'canManage' => request()->user()->can('support.manage'),
            'canUpdate' => request()->user()->can('update', $support),
            'canConvert' => request()->user()->can('convert', $support) && $support->status !== 'converted',
        ]);
    }

    public function edit(SupportTicket $support): Response
    {
        $this->authorize('update', $support);

        return Inertia::render('Support/Form', [
            'ticket' => $support,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'systems' => System::orderBy('name')->get(['id', 'name']),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'categories' => SupportTicket::CATEGORIES,
            'priorities' => SupportTicket::PRIORITIES,
            'statuses' => SupportTicket::STATUSES,
        ]);
    }

    public function update(Request $request, SupportTicket $support): RedirectResponse
    {
        $this->authorize('update', $support);
        $previous = $support->status;
        $previousAssignee = $support->assignee_id;
        $data = $this->validated($request, $support);

        if (! $request->user()->can('support.manage')) {
            $data = array_intersect_key($data, array_flip(['status', 'description']));
        }

        $support->fill($data);
        if (isset($data['priority']) && $data['priority'] !== $support->getOriginal('priority')) {
            $support->applySlaFromPriority();
        }
        $support->applyStatusTimestamps($previous);
        $support->save();

        if (! empty($data['assignee_id']) && (int) $data['assignee_id'] !== (int) $previousAssignee) {
            User::find($data['assignee_id'])?->notify(new SupportTicketAssignedNotification($support));
        }

        return redirect()->route('support.show', $support)->with('success', 'Ticket updated.');
    }

    public function destroy(SupportTicket $support): RedirectResponse
    {
        $this->authorize('delete', $support);
        $support->delete();

        return redirect()->route('support.index')->with('success', 'Ticket deleted.');
    }

    public function storeComment(Request $request, SupportTicket $support): RedirectResponse
    {
        $this->authorize('update', $support);
        $data = $request->validate(['body' => ['required', 'string']]);
        SupportComment::create([
            'support_ticket_id' => $support->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        if ($support->first_responded_at === null) {
            $support->update(['first_responded_at' => now()]);
        }

        return redirect()->back()->with('success', 'Comment added.');
    }

    public function convert(Request $request, SupportTicket $support): RedirectResponse
    {
        $this->authorize('convert', $support);
        abort_if($support->status === 'converted', 422, 'Already converted.');

        $data = $request->validate([
            'target' => ['required', 'in:bug,task,feature'],
        ]);

        DB::transaction(function () use ($support, $data, $request) {
            if ($data['target'] === 'bug') {
                $bug = Bug::create([
                    'title' => $support->title,
                    'description' => $support->description,
                    'severity' => $support->priority,
                    'status' => 'reported',
                    'environment' => 'production',
                    'reporter_id' => $request->user()->id,
                    'assignee_id' => $support->assignee_id,
                    'delivery_project_id' => $support->delivery_project_id,
                    'system_id' => $support->system_id,
                ]);
                $support->converted_bug_id = $bug->id;
            } elseif ($data['target'] === 'task') {
                abort_unless($support->delivery_project_id, 422, 'Delivery project required to convert to task.');
                $task = Task::create([
                    'delivery_project_id' => $support->delivery_project_id,
                    'title' => $support->title,
                    'description' => $support->description,
                    'assignee_id' => $support->assignee_id,
                    'status' => 'backlog',
                    'priority' => $support->priority === 'critical' ? 'critical' : 'high',
                ]);
                $support->converted_task_id = $task->id;
            } else {
                abort_unless($support->delivery_project_id, 422, 'Delivery project required to convert to feature.');
                abort_unless($support->system_id, 422, 'System required to convert to feature.');
                $feature = Feature::create([
                    'system_id' => $support->system_id,
                    'delivery_project_id' => $support->delivery_project_id,
                    'title' => $support->title,
                    'description' => $support->description,
                    'status' => 'planned',
                    'priority' => 'medium',
                    'dod_checklist' => Feature::defaultDodChecklist(),
                ]);
                $support->converted_feature_id = $feature->id;
            }

            $support->status = 'converted';
            if ($support->first_responded_at === null) {
                $support->first_responded_at = now();
            }
            $support->resolved_at = now();
            $support->save();
        });

        return redirect()->route('support.show', $support)->with('success', 'Ticket converted.');
    }

    public function updateSlas(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('support.manage'), 403);
        $data = $request->validate([
            'slas' => ['required', 'array'],
            'slas.*.priority' => ['required', 'in:'.implode(',', SupportTicket::PRIORITIES)],
            'slas.*.response_minutes' => ['required', 'integer', 'min:1'],
            'slas.*.resolution_minutes' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($data['slas'] as $row) {
            SupportSla::query()->updateOrCreate(
                ['priority' => $row['priority']],
                [
                    'response_minutes' => $row['response_minutes'],
                    'resolution_minutes' => $row['resolution_minutes'],
                ]
            );
        }

        return redirect()->back()->with('success', 'SLA matrix updated.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?SupportTicket $ticket = null): array
    {
        $titleRule = $ticket && ! $request->user()->can('support.manage') ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$titleRule, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'in:'.implode(',', SupportTicket::CATEGORIES)],
            'priority' => ['nullable', 'string', 'in:'.implode(',', SupportTicket::PRIORITIES)],
            'status' => ['nullable', 'string', 'in:'.implode(',', SupportTicket::STATUSES)],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'system_id' => ['nullable', 'exists:systems,id'],
            'delivery_project_id' => ['nullable', 'exists:delivery_projects,id'],
        ]);
    }
}

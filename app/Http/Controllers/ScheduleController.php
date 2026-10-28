<?php

namespace App\Http\Controllers;

use App\Models\DeliveryProject;
use App\Models\User;
use App\Services\ScheduleTimelineService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(private ScheduleTimelineService $timeline) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->can('delivery_projects.view'), 403);
        abort_unless(
            $user->can('dashboards.team')
            || $user->can('dashboards.executive')
            || $user->hasRole('admin'),
            403
        );

        $from = $request->filled('from')
            ? now()->parse($request->string('from'))->startOfDay()
            : null;
        $to = $request->filled('to')
            ? now()->parse($request->string('to'))->endOfDay()
            : null;

        $schedule = $this->timeline->portfolio($from, $to, [
            'project_id' => $request->input('project_id') ?: null,
            'assignee_id' => $request->input('assignee_id') ?: null,
        ]);

        return Inertia::render('Schedule/Index', [
            'schedule' => $schedule,
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'viewMode' => $request->input('view', 'Month'),
        ]);
    }
}

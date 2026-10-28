<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BugController;
use App\Http\Controllers\BusinessProblemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryProjectController;
use App\Http\Controllers\Dmaic\DmaicProfileController;
use App\Http\Controllers\EpicController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\StrategicPillarController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboards/executive', [DashboardController::class, 'executive'])->name('dashboards.executive');
    Route::get('/dashboards/developer', [DashboardController::class, 'developer'])->name('dashboards.developer');
    Route::get('/dashboards/team', [DashboardController::class, 'team'])->name('dashboards.team');
    Route::get('/dashboards/dmaic', [DashboardController::class, 'dmaic'])->name('dashboards.dmaic');

    Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
    Route::redirect('/roadmap', '/portfolio');
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');

    Route::get('/pillars', [StrategicPillarController::class, 'index'])->name('pillars.index');
    Route::post('/pillars', [StrategicPillarController::class, 'store'])->name('pillars.store');
    Route::get('/pillars/{strategicPillar}', [StrategicPillarController::class, 'show'])->name('pillars.show');
    Route::put('/pillars/{strategicPillar}', [StrategicPillarController::class, 'update'])->name('pillars.update');
    Route::delete('/pillars/{strategicPillar}', [StrategicPillarController::class, 'destroy'])->name('pillars.destroy');

    Route::resource('initiatives', InitiativeController::class);
    Route::resource('business-problems', BusinessProblemController::class)->parameters([
        'business-problems' => 'businessProblem',
    ]);
    Route::resource('delivery-projects', DeliveryProjectController::class)->parameters([
        'delivery-projects' => 'deliveryProject',
    ]);
    Route::post('/delivery-projects/{deliveryProject}/milestones', [DeliveryProjectController::class, 'storeMilestone'])->name('delivery-projects.milestones.store');
    Route::put('/delivery-projects/{deliveryProject}/milestones/{milestone}', [DeliveryProjectController::class, 'updateMilestone'])->name('delivery-projects.milestones.update');
    Route::post('/delivery-projects/{deliveryProject}/dmaic/bootstrap', [DmaicProfileController::class, 'bootstrap'])->name('delivery-projects.dmaic.bootstrap');

    Route::resource('systems', SystemController::class);
    Route::resource('epics', EpicController::class);
    Route::resource('features', FeatureController::class);
    Route::post('/features/{feature}/assign', [FeatureController::class, 'assign'])->name('features.assign');
    Route::post('/features/{feature}/unassign', [FeatureController::class, 'unassign'])->name('features.unassign');
    Route::resource('tasks', TaskController::class);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status.update');
    Route::post('/tasks/{task}/comments', [TaskController::class, 'storeComment'])->name('tasks.comments.store');

    Route::resource('bugs', BugController::class);

    Route::resource('support', SupportTicketController::class)->parameters([
        'support' => 'support',
    ]);
    Route::post('/support/{support}/comments', [SupportTicketController::class, 'storeComment'])->name('support.comments.store');
    Route::post('/support/{support}/convert', [SupportTicketController::class, 'convert'])->name('support.convert');
    Route::put('/support-slas', [SupportTicketController::class, 'updateSlas'])->name('support.slas.update');

    Route::resource('releases', ReleaseController::class);
    Route::post('/releases/{release}/items', [ReleaseController::class, 'storeItem'])->name('releases.items.store');
    Route::delete('/releases/{release}/items/{item}', [ReleaseController::class, 'destroyItem'])->name('releases.items.destroy');
    Route::post('/releases/{release}/deployments', [ReleaseController::class, 'storeDeployment'])->name('releases.deployments.store');
    Route::post('/releases/{release}/mark-deployed', [ReleaseController::class, 'markDeployed'])->name('releases.mark-deployed');

    Route::resource('sprints', SprintController::class);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/dmaic', [DmaicProfileController::class, 'index'])->name('dmaic.index');
    Route::get('/dmaic/{dmaic}', [DmaicProfileController::class, 'show'])->name('dmaic.show');
    Route::put('/dmaic/deliverables/{deliverable}', [DmaicProfileController::class, 'updateDeliverable'])->name('dmaic.deliverables.update');
    Route::post('/dmaic/gates/{gate}/approve', [DmaicProfileController::class, 'approveGate'])->name('dmaic.gates.approve');
    Route::post('/dmaic/gates/{gate}/reject', [DmaicProfileController::class, 'rejectGate'])->name('dmaic.gates.reject');
    Route::post('/dmaic/{dmaic}/measurement-plans', [DmaicProfileController::class, 'storeMeasurementPlan'])->name('dmaic.measurement-plans.store');
    Route::post('/dmaic/{dmaic}/root-causes', [DmaicProfileController::class, 'storeRootCause'])->name('dmaic.root-causes.store');
    Route::put('/dmaic/root-causes/{rootCause}', [DmaicProfileController::class, 'updateRootCause'])->name('dmaic.root-causes.update');
    Route::post('/dmaic/{dmaic}/improvements', [DmaicProfileController::class, 'storeImprovement'])->name('dmaic.improvements.store');
    Route::post('/dmaic/{dmaic}/control-plans', [DmaicProfileController::class, 'storeControlPlan'])->name('dmaic.control-plans.store');
    Route::put('/dmaic/control-metrics/{metric}', [DmaicProfileController::class, 'updateControlMetric'])->name('dmaic.control-metrics.update');

    Route::resource('kpis', KpiController::class);
    Route::post('/kpis/{kpi}/measurements', [KpiController::class, 'storeMeasurement'])->name('kpis.measurements.store');

    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::put('/admin/users/{user}/roles', [AdminUserController::class, 'updateRoles'])->name('admin.users.roles');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

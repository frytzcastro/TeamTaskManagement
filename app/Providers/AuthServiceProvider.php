<?php

namespace App\Providers;

use App\Models\Bug;
use App\Models\BusinessProblem;
use App\Models\DeliveryProject;
use App\Models\Dmaic\DmaicProfile;
use App\Models\Epic;
use App\Models\Feature;
use App\Models\Initiative;
use App\Models\Kpi;
use App\Models\System;
use App\Models\Release;
use App\Models\Sprint;
use App\Models\StrategicPillar;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\User;
use App\Policies\BugPolicy;
use App\Policies\BusinessProblemPolicy;
use App\Policies\DeliveryProjectPolicy;
use App\Policies\DmaicProfilePolicy;
use App\Policies\EpicPolicy;
use App\Policies\FeaturePolicy;
use App\Policies\InitiativePolicy;
use App\Policies\KpiPolicy;
use App\Policies\SystemPolicy;
use App\Policies\ReleasePolicy;
use App\Policies\SprintPolicy;
use App\Policies\StrategicPillarPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        StrategicPillar::class => StrategicPillarPolicy::class,
        Initiative::class => InitiativePolicy::class,
        BusinessProblem::class => BusinessProblemPolicy::class,
        DeliveryProject::class => DeliveryProjectPolicy::class,
        System::class => SystemPolicy::class,
        Epic::class => EpicPolicy::class,
        Feature::class => FeaturePolicy::class,
        Task::class => TaskPolicy::class,
        Bug::class => BugPolicy::class,
        SupportTicket::class => SupportTicketPolicy::class,
        Release::class => ReleasePolicy::class,
        Sprint::class => SprintPolicy::class,
        Kpi::class => KpiPolicy::class,
        DmaicProfile::class => DmaicProfilePolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}

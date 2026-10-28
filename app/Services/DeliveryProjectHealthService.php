<?php

namespace App\Services;

use App\Models\DeliveryProject;
use Carbon\Carbon;

class DeliveryProjectHealthService
{
    public function calculate(DeliveryProject $deliveryProject): string
    {
        $deliveryProject->loadMissing('tasks');

        $tasks = $deliveryProject->tasks;
        $openTasks = $tasks->whereNotIn('status', ['done', 'released']);
        $blocked = $openTasks->where('status', 'blocked')->count()
            + $openTasks->filter(fn ($t) => filled($t->blockers))->count();
        $overdue = $openTasks->filter(fn ($t) => $t->due_date && $t->due_date->isPast())->count();

        $scheduleSlip = $deliveryProject->target_completion
            && $deliveryProject->target_completion->isPast()
            && ! in_array($deliveryProject->status, ['completed', 'done'], true);

        if ($scheduleSlip || $blocked >= 3 || $overdue >= 5) {
            return 'red';
        }

        if ($blocked >= 1 || $overdue >= 2 || ($deliveryProject->target_completion && $deliveryProject->target_completion->lte(Carbon::today()->addDays(7)) && $openTasks->isNotEmpty())) {
            return 'amber';
        }

        return 'green';
    }

    public function refresh(DeliveryProject $deliveryProject): DeliveryProject
    {
        $deliveryProject->health = $this->calculate($deliveryProject);
        $deliveryProject->save();

        return $deliveryProject;
    }
}

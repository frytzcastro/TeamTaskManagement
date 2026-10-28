<?php

namespace App\Console\Commands;

use App\Models\DeliveryProject;
use App\Services\MilestoneSyncService;
use Illuminate\Console\Command;

class SyncProjectMilestones extends Command
{
    protected $signature = 'milestones:sync {--project= : Delivery project UUID}';

    protected $description = 'Reconcile auto milestones from epics and releases';

    public function handle(MilestoneSyncService $sync): int
    {
        $query = DeliveryProject::query()->with(['epics', 'releases']);

        if ($id = $this->option('project')) {
            $query->whereKey($id);
        }

        $count = 0;
        $query->each(function (DeliveryProject $project) use ($sync, &$count) {
            $sync->reconcileProject($project);
            $count++;
        });

        $this->info("Reconciled milestones for {$count} project(s).");

        return self::SUCCESS;
    }
}

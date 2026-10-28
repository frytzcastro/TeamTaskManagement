<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DeliveryProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ReturnsToDeliveryProjectHub
{
    /** @var list<string> */
    private const HUB_RETURN_TABS = ['overview', 'plan', 'build', 'quality', 'ship', 'improve', 'schedule'];

    protected function hubReturnTab(Request $request): ?string
    {
        $tab = $request->input('return_tab');
        if (! is_string($tab) || ! in_array($tab, self::HUB_RETURN_TABS, true)) {
            return null;
        }

        return $tab;
    }

    protected function isHubReturnRequest(Request $request): bool
    {
        if ($this->hubReturnTab($request) !== null) {
            return true;
        }

        $returnProjectId = $request->input('return_project_id');

        return is_string($returnProjectId) && $returnProjectId !== '';
    }

    protected function hubReturnProjectId(Request $request): ?string
    {
        if (! $this->isHubReturnRequest($request)) {
            return null;
        }

        $id = $request->input('return_project_id') ?: $request->input('delivery_project_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * @return array{returnProject: array{id: string, name: string}|null, returnTab: string|null}
     */
    protected function hubReturnProps(Request $request): array
    {
        $projectId = $this->hubReturnProjectId($request);
        $tab = $this->hubReturnTab($request);
        $project = $projectId
            ? DeliveryProject::query()->whereKey($projectId)->first(['id', 'name'])
            : null;

        return [
            'returnProject' => $project
                ? ['id' => $project->id, 'name' => $project->name]
                : null,
            'returnTab' => $project ? ($tab ?: 'overview') : null,
        ];
    }

    protected function redirectAfterHubCreate(Request $request, string $fallbackRoute, mixed $fallbackParam, string $message): RedirectResponse
    {
        $projectId = $this->hubReturnProjectId($request);
        $tab = $this->hubReturnTab($request) ?: 'overview';

        if ($projectId && DeliveryProject::query()->whereKey($projectId)->exists()) {
            return redirect()
                ->route('delivery-projects.show', ['deliveryProject' => $projectId, 'tab' => $tab])
                ->with('success', $message);
        }

        return redirect()->route($fallbackRoute, $fallbackParam)->with('success', $message);
    }
}

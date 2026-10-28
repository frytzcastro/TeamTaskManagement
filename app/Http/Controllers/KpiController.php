<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use App\Models\Kpi;
use App\Models\KpiMeasurement;
use App\Models\DeliveryProject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KpiController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Kpi::class);

        return Inertia::render('Kpis/Index', [
            'kpis' => Kpi::query()->with('owner')->withCount('measurements')->latest()->get(),
            'canManage' => $request->user()->can('kpis.manage'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Kpi::class);

        return Inertia::render('Kpis/Form', [
            'kpi' => null,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'initiatives' => Initiative::orderBy('name')->get(['id', 'name']),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Kpi::class);

        $data = $this->validated($request);
        $initiativeIds = $data['initiative_ids'] ?? [];
        $deliveryProjectIds = $data['delivery_project_ids'] ?? [];
        unset($data['initiative_ids'], $data['delivery_project_ids']);

        $kpi = Kpi::create($data);
        $kpi->initiatives()->sync($initiativeIds);
        $kpi->deliveryProjects()->sync($deliveryProjectIds);

        return redirect()->route('kpis.show', $kpi)->with('success', 'KPI created.');
    }

    public function show(Kpi $kpi): Response
    {
        $this->authorize('view', $kpi);

        $kpi->load(['owner', 'initiatives', 'deliveryProjects', 'measurements' => fn ($q) => $q->latest('measured_at')->limit(20)]);

        return Inertia::render('Kpis/Show', [
            'kpi' => $kpi,
            'canManage' => request()->user()->can('kpis.manage'),
        ]);
    }

    public function edit(Kpi $kpi): Response
    {
        $this->authorize('update', $kpi);

        $kpi->load(['initiatives', 'deliveryProjects']);

        return Inertia::render('Kpis/Form', [
            'kpi' => $kpi,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'initiatives' => Initiative::orderBy('name')->get(['id', 'name']),
            'deliveryProjects' => DeliveryProject::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Kpi $kpi): RedirectResponse
    {
        $this->authorize('update', $kpi);

        $data = $this->validated($request);
        $initiativeIds = $data['initiative_ids'] ?? [];
        $deliveryProjectIds = $data['delivery_project_ids'] ?? [];
        unset($data['initiative_ids'], $data['delivery_project_ids']);

        $kpi->update($data);
        $kpi->initiatives()->sync($initiativeIds);
        $kpi->deliveryProjects()->sync($deliveryProjectIds);

        return redirect()->route('kpis.show', $kpi)->with('success', 'KPI updated.');
    }

    public function destroy(Kpi $kpi): RedirectResponse
    {
        $this->authorize('delete', $kpi);
        $kpi->delete();

        return redirect()->route('kpis.index')->with('success', 'KPI deleted.');
    }

    public function storeMeasurement(Request $request, Kpi $kpi): RedirectResponse
    {
        $this->authorize('update', $kpi);

        $data = $request->validate([
            'value' => ['required', 'numeric'],
            'measured_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'trend' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        KpiMeasurement::create([
            'kpi_id' => $kpi->id,
            'value' => $data['value'],
            'measured_at' => $data['measured_at'],
            'notes' => $data['notes'] ?? null,
        ]);

        $kpi->update([
            'current_value' => $data['value'],
            'trend' => $data['trend'] ?? $kpi->trend,
            'status' => $data['status'] ?? $this->deriveStatus($kpi->fresh(), (float) $data['value']),
        ]);

        return redirect()->back()->with('success', 'Measurement recorded.');
    }

    private function deriveStatus(Kpi $kpi, float $value): string
    {
        if ($kpi->target === null) {
            return 'no_data';
        }

        $target = (float) $kpi->target;
        $baseline = $kpi->baseline !== null ? (float) $kpi->baseline : null;
        $lowerIsBetter = $kpi->direction === 'lower_is_better';

        if ($lowerIsBetter) {
            if ($value <= $target) {
                return 'green';
            }
            if ($baseline !== null && $value <= $baseline) {
                return 'amber';
            }

            return 'red';
        }

        if ($value >= $target) {
            return 'green';
        }
        if ($baseline !== null && $value >= $baseline) {
            return 'amber';
        }

        return 'red';
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'metric_layer' => ['nullable', 'string', 'in:'.implode(',', Kpi::METRIC_LAYERS)],
            'name' => ['required', 'string', 'max:255'],
            'definition' => ['nullable', 'string'],
            'formula' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'data_source' => ['nullable', 'string', 'max:255'],
            'baseline' => ['nullable', 'numeric'],
            'target' => ['nullable', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'reporting_period' => ['nullable', 'string', 'max:100'],
            'direction' => ['nullable', 'in:lower_is_better,higher_is_better'],
            'status' => ['nullable', 'string', 'max:50'],
            'trend' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:50'],
            'initiative_ids' => ['nullable', 'array'],
            'initiative_ids.*' => ['uuid', 'exists:initiatives,id'],
            'delivery_project_ids' => ['nullable', 'array'],
            'delivery_project_ids.*' => ['uuid', 'exists:delivery_projects,id'],
        ]);
    }
}

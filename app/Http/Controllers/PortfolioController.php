<?php

namespace App\Http\Controllers;

use App\Models\StrategicPillar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('pillars.view'), 403);

        $pillars = StrategicPillar::query()
            ->with([
                'initiatives' => function ($q) {
                    $q->orderBy('name')
                        ->with([
                            'deliveryProjects' => fn ($pq) => $pq
                                ->orderBy('name')
                                ->select(['id', 'initiative_id', 'name', 'type', 'status', 'health', 'code']),
                            'kpis' => fn ($kq) => $kq->select([
                                'kpis.id',
                                'kpis.name',
                                'kpis.status',
                                'kpis.current_value',
                                'kpis.target',
                                'kpis.baseline',
                                'kpis.metric_layer',
                            ]),
                        ])
                        ->withCount('deliveryProjects');
                },
            ])
            ->withCount('initiatives')
            ->orderBy('name')
            ->get();

        $initiativeCount = $pillars->sum('initiatives_count');
        $projectCount = $pillars->sum(
            fn ($pillar) => $pillar->initiatives->sum('delivery_projects_count')
        );
        $atRiskCount = $pillars->sum(
            fn ($pillar) => $pillar->initiatives->filter(function ($initiative) {
                if (in_array($initiative->status, ['at_risk', 'delayed', 'off_track'], true)) {
                    return true;
                }

                return $initiative->kpis->contains(
                    fn ($kpi) => in_array($kpi->status, ['at_risk', 'off_track', 'red', 'amber'], true)
                );
            })->count()
        );

        return Inertia::render('Portfolio/Index', [
            'pillars' => $pillars,
            'summary' => [
                'pillars' => $pillars->count(),
                'initiatives' => $initiativeCount,
                'delivery_projects' => $projectCount,
                'at_risk_initiatives' => $atRiskCount,
            ],
        ]);
    }
}

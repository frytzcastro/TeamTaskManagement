<?php

namespace App\Http\Controllers;

use App\Models\BusinessProblem;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessProblemController extends Controller
{
    use Concerns\ReturnsToDeliveryProjectHub;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BusinessProblem::class);

        return Inertia::render('BusinessProblems/Index', [
            'problems' => BusinessProblem::with(['initiative', 'owner'])->latest()->paginate(25),
            'canManage' => $request->user()->can('business_problems.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', BusinessProblem::class);

        return Inertia::render('BusinessProblems/Form', [
            'problem' => null,
            'initiatives' => Initiative::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'defaultInitiativeId' => $request->input('initiative_id') ?: null,
            ...$this->hubReturnProps($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', BusinessProblem::class);
        $problem = BusinessProblem::create($this->validated($request));

        return $this->redirectAfterHubCreate($request, 'business-problems.show', $problem, 'Business problem created.');
    }

    public function show(BusinessProblem $businessProblem): Response
    {
        $this->authorize('view', $businessProblem);
        $businessProblem->load(['initiative', 'owner', 'dmaicProfiles.deliveryProject']);

        return Inertia::render('BusinessProblems/Show', [
            'problem' => $businessProblem,
            'canManage' => request()->user()->can('business_problems.manage'),
        ]);
    }

    public function edit(BusinessProblem $businessProblem): Response
    {
        $this->authorize('update', $businessProblem);

        return Inertia::render('BusinessProblems/Form', [
            'problem' => $businessProblem,
            'initiatives' => Initiative::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, BusinessProblem $businessProblem): RedirectResponse
    {
        $this->authorize('update', $businessProblem);
        $businessProblem->update($this->validated($request));

        return redirect()->route('business-problems.show', $businessProblem)->with('success', 'Business problem updated.');
    }

    public function destroy(BusinessProblem $businessProblem): RedirectResponse
    {
        $this->authorize('delete', $businessProblem);
        $businessProblem->delete();

        return redirect()->route('business-problems.index')->with('success', 'Business problem deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'initiative_id' => ['required', 'exists:initiatives,id'],
            'title' => ['required', 'string', 'max:255'],
            'problem_statement' => ['nullable', 'string'],
            'business_impact' => ['nullable', 'string'],
            'customer_impact' => ['nullable', 'string'],
            'opportunity' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ]);
    }
}

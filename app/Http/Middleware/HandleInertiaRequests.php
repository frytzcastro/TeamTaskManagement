<?php

namespace App\Http\Middleware;

use App\Models\DeliveryProject;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'nav' => [
                'my_projects' => $user && $user->can('delivery_projects.view')
                    ? DeliveryProject::query()
                        ->where(function ($q) use ($user) {
                            $q->where('project_owner_id', $user->id)
                                ->orWhere('product_owner_id', $user->id)
                                ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id));
                        })
                        ->orderByDesc('updated_at')
                        ->limit(8)
                        ->get(['id', 'name', 'type', 'status'])
                    : [],
            ],
            'notifications' => [
                'unread_count' => $user ? $user->unreadNotifications()->count() : 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }
}

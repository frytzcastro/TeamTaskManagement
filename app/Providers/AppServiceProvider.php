<?php

namespace App\Providers;

use App\Models\Dmaic\DmaicProfile;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Behind Docker port publish (e.g. host :8080 → container :80), the
        // request port Laravel sees is 80, which breaks absolute Vite/Ziggy URLs.
        if ($root = config('app.url')) {
            URL::forceRootUrl($root);
        }

        Vite::prefetch(concurrency: 3);

        Route::bind('dmaic', fn ($value) => DmaicProfile::findOrFail($value));
        Route::bind('support', fn ($value) => SupportTicket::findOrFail($value));
    }
}

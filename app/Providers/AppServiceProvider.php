<?php

namespace App\Providers;

use App\Models\ProjectLive;
use App\Observers\ProjectLiveObserver;
use App\Policies\ProjectLivePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ProjectLive::observe(ProjectLiveObserver::class);

        Gate::policy(ProjectLive::class, ProjectLivePolicy::class);

        RateLimiter::for('tiktok-gift-webhook', function ($request) {
            return Limit::perMinute(300)->by($request->input('project_live_id') ?? $request->ip());
        });
    }
}

<?php

namespace App\Providers;

use App\Models\ProjectLive;
use App\Observers\ProjectLiveObserver;
use App\Policies\ProjectLivePolicy;
use Illuminate\Support\Facades\Gate;
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
    }
}

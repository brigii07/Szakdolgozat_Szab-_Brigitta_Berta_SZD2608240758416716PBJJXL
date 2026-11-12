<?php

namespace App\Providers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Gate::define('manage-diagnosis', fn(User $user) => $user->role === 'doctor');
        Gate::define('use-ai-assistant', fn(User $user) => $user->role === 'doctor');
        Gate::define('issue-documents', fn(User $user) => $user->role === 'doctor');
        Gate::define('view-full-history', fn(User $user) => $user->role === 'doctor');
        Gate::define('view-stats', fn(User $user) => $user->role === 'doctor');
        Gate::define('export-data', fn(User $user) => $user->role === 'doctor');

        View::composer('partials.topbar', function ($view) {
            $view->with('urgentCount', auth()->check() ? Patient::where('is_urgent', true)->where('status', '!=', Patient::STATUS_DIAGNOSED)->count() : 0);
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Branch;
use App\Policies\BranchPolicy;

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
        // Allow developer user (no role, no branch) to bypass authorization checks
        Gate::before(function ($user, $ability) {
            // Developer user has null role_id and null branch_id
            if (
                $user &&
                $user->role_id === null &&
                $user->branch_id === null &&
                strtolower(trim((string) ($user->email ?? ''))) === 'ikuan4@gmail.com'
            ) {
                return true;
            }
            return null;
        });

        // Register Branch policy mapping in case AuthServiceProvider is not registered
        Gate::policy(Branch::class, BranchPolicy::class);
    }
}

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
        // Allow users with the "Developer" role to bypass authorization checks in local/dev environment
        Gate::before(function ($user, $ability) {
            try {
                if ($user && isset($user->role) && $user->role && strtolower($user->role->name) === 'developer') {
                    return true;
                }
            } catch (\Throwable $e) {
                // If something goes wrong reading role, don't block normal gate resolution
            }
            return null;
        });

        // Register Branch policy mapping in case AuthServiceProvider is not registered
        try {
            Gate::policy(Branch::class, BranchPolicy::class);
        } catch (\Throwable $e) {
            // ignore if policy cannot be registered at this stage
        }
    }
}

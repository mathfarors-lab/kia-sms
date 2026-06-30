<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Use our custom KIA pagination view
        Paginator::defaultView('vendor.pagination.kia');

        // Super-admin bypasses all permission gates
        Gate::before(function (\App\Models\User $user, string $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
            // Check spatie permission directly so authorize() works for permission strings
            if ($user->hasPermissionTo($ability)) {
                return true;
            }
        });
    }
}

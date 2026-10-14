<?php

namespace App\Providers;

use App\Models\User;
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
        // Dipakai di Blade untuk menampilkan menu sesuai role: @can('admin') ... @endcan
        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('approver', fn (User $user) => $user->isApprover());
    }
}

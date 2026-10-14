<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

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

        // Setiap activity log (DB) juga ditulis ke file harian storage/logs/activity-*.log
        Activity::created(function (Activity $activity) {
            Log::channel('activity')->info("[{$activity->log_name}] {$activity->description}", array_filter([
                'causer_id' => $activity->causer_id,
                'subject' => $activity->subject_type ? class_basename($activity->subject_type).'#'.$activity->subject_id : null,
                'event' => $activity->event,
                'properties' => $activity->properties?->toArray() ?: null,
            ]));
        });
    }
}

<?php

namespace App\Providers;

use App\Enums\ApprovalStatus;
use App\Models\Location;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
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

        // Pagination bergaya SekawanFleet
        Paginator::defaultView('pagination.sekawan');

        // Data sidebar: jumlah persetujuan menunggu & ringkasan lokasi
        View::composer('layouts.sidebar', function ($view) {
            $user = auth()->user();

            $types = Location::query()->selectRaw('type, COUNT(*) AS total')->groupBy('type')->pluck('total', 'type');

            $view->with([
                'pendingApprovals' => $user?->isApprover()
                    ? $user->approvals()->where('status', ApprovalStatus::Pending)->count()
                    : 0,
                'locationSummary' => [
                    'total' => (int) $types->sum(),
                    'label' => sprintf('%d Pusat · %d Cabang · %d Site',
                        $types['head_office'] ?? 0, $types['branch_office'] ?? 0, $types['mine'] ?? 0),
                ],
            ]);
        });

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

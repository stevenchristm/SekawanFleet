<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Location;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public const PERIODS = [3, 6, 12];

    public function __invoke(Request $request, DashboardService $dashboard): View
    {
        $months = (int) $request->query('months', 6);
        if (! in_array($months, self::PERIODS, true)) {
            $months = 6;
        }

        $locations = Location::orderBy('id')->get(['id', 'name']);
        $locationId = (int) $request->query('location') ?: null;
        if ($locationId && ! $locations->contains('id', $locationId)) {
            $locationId = null;
        }

        $user = $request->user();

        return view('dashboard', [
            'months' => $months,
            'periods' => self::PERIODS,
            'locations' => $locations,
            'locationId' => $locationId,
            'kpis' => $dashboard->kpis($locationId),
            'approverKpis' => $user->isApprover() ? $dashboard->approverKpis($user) : null,
            'serviceKpis' => $user->isAdmin() ? $dashboard->serviceKpis($locationId) : null,
            'charts' => $dashboard->charts($months, $locationId),
            'recent' => $user->isAdmin()
                ? Booking::with(['vehicle.location', 'driver'])
                    ->when($locationId, fn ($q, $id) => $q->whereHas('vehicle', fn ($v) => $v->where('location_id', $id)))
                    ->latest()->limit(5)->get()
                : collect(),
        ]);
    }
}

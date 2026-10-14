<?php

namespace App\Http\Controllers;

use App\Models\Booking;
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

        $user = $request->user();

        return view('dashboard', [
            'months' => $months,
            'periods' => self::PERIODS,
            'kpis' => $dashboard->kpis(),
            'approverKpis' => $user->isApprover() ? $dashboard->approverKpis($user) : null,
            'charts' => $dashboard->charts($months),
            'recent' => $user->isAdmin()
                ? Booking::with(['vehicle', 'driver'])->latest()->limit(5)->get()
                : collect(),
        ]);
    }
}

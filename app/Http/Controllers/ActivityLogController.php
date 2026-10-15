<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /** Kategori log (log_name) beserta labelnya. */
    public const CATEGORIES = [
        'auth' => 'Autentikasi',
        'booking' => 'Pemesanan',
        'approval' => 'Persetujuan',
        'report' => 'Laporan',
        'fleet' => 'Armada',
        'service' => 'Servis',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'log' => ['nullable', Rule::in(array_keys(self::CATEGORIES))],
            'user' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($filters['log'] ?? null, fn ($q, $log) => $q->where('log_name', $log))
            ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('causer_type', User::class)->where('causer_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('created_at', '<', Carbon::parse($d)->addDay()->startOfDay()))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('description', 'ilike', '%'.addcslashes($term, '%_\\').'%'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity-logs.index', [
            'activities' => $activities,
            'filters' => $filters,
            'categories' => self::CATEGORIES,
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}

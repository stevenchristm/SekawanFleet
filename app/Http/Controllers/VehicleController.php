<?php

namespace App\Http\Controllers;

use App\Enums\Ownership;
use App\Enums\ServiceStatus;
use App\Enums\ServiceType;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Booking;
use App\Models\Location;
use App\Models\ServiceSchedule;
use App\Models\Vehicle;
use App\Models\VehicleUsage;
use App\Services\BookingService;
use App\Services\FleetStatsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(private FleetStatsService $stats) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:50'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'type' => ['nullable', Rule::enum(VehicleType::class)],
            'ownership' => ['nullable', Rule::enum(Ownership::class)],
            'service' => ['nullable', Rule::in(['overdue', 'soon'])],
        ]);

        $vehicles = Vehicle::query()
            ->with(['location', 'rentalCompany', 'nextService', 'lastService'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('plate_number', 'ilike', '%'.addcslashes($term, '%_\\').'%')
                ->orWhere('brand_model', 'ilike', '%'.addcslashes($term, '%_\\').'%')))
            ->when($filters['location_id'] ?? null, fn ($q, $id) => $q->where('location_id', $id))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['ownership'] ?? null, fn ($q, $own) => $q->where('ownership', $own))
            ->when($filters['service'] ?? null, fn ($q, $state) => $q->whereHas('services',
                fn ($s) => $state === 'overdue' ? $s->overdue() : $s->dueSoon()))
            ->orderBy('plate_number')
            ->paginate(15)
            ->withQueryString();

        return view('vehicles.index', [
            'vehicles' => $vehicles,
            'totals' => $this->stats->usageTotals($vehicles->pluck('id')->all()),
            'summary' => [
                'total' => Vehicle::count(),
                'available' => Vehicle::where('status', VehicleStatus::Available)->count(),
                'overdue' => ServiceSchedule::overdue()->distinct('vehicle_id')->count('vehicle_id'),
                'soon' => ServiceSchedule::dueSoon()->distinct('vehicle_id')->count('vehicle_id'),
            ],
            'filters' => $filters,
            'locations' => Location::orderBy('id')->get(['id', 'name']),
            'types' => VehicleType::cases(),
            'ownerships' => Ownership::cases(),
        ]);
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load(['location', 'rentalCompany']);

        $services = $vehicle->services()->with(['creator', 'completer'])->get();

        return view('vehicles.show', [
            'vehicle' => $vehicle,
            'totals' => $this->stats->usageTotals([$vehicle->id])->get($vehicle->id),
            'monthly' => $this->stats->monthly($vehicle->id),
            'scheduled' => $services->where('status', ServiceStatus::Scheduled)->sortBy('scheduled_date')->values(),
            'history' => $services->where('status', '!=', ServiceStatus::Scheduled)
                ->sortByDesc(fn ($s) => $s->completed_date ?? $s->scheduled_date)->values(),
            'usages' => VehicleUsage::query()
                ->with('booking.driver')
                ->whereHas('booking', fn ($q) => $q->where('vehicle_id', $vehicle->id))
                ->latest('returned_at')
                ->paginate(8)
                ->withQueryString(),
            'upcoming' => Booking::query()
                ->where('vehicle_id', $vehicle->id)
                ->whereIn('status', BookingService::BLOCKING_STATUSES)
                ->where('end_at', '>=', now())
                ->orderBy('start_at')
                ->limit(5)
                ->get(),
            'serviceTypes' => ServiceType::cases(),
        ]);
    }
}

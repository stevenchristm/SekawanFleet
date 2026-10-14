<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $bookings = Booking::query()
            ->with(['vehicle', 'driver', 'currentApproval.approver'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('code', 'ilike', "%{$term}%")
                ->orWhere('requester_name', 'ilike', "%{$term}%")
                ->orWhere('destination', 'ilike', "%{$term}%")))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('start_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('start_at', '<', Carbon::parse($d)->addDay()))
            ->latest('start_at')
            ->paginate(15)
            ->withQueryString();

        return view('bookings.index', [
            'bookings' => $bookings,
            'statuses' => BookingStatus::cases(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('bookings.create', [
            'vehicles' => Vehicle::with('location')
                ->where('status', VehicleStatus::Available)->orderBy('plate_number')->get(),
            'drivers' => Driver::with('location')->where('is_active', true)->orderBy('name')->get(),
            'approvers' => User::where('role', UserRole::Approver)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $booking = $this->bookings->create($request->validated(), $request->user());

        return redirect()->route('bookings.show', $booking)
            ->with('success', "Pemesanan {$booking->code} berhasil dibuat dan menunggu persetujuan level 1.");
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['vehicle.location', 'vehicle.rentalCompany', 'driver', 'creator', 'approvals.approver']);

        return view('bookings.show', compact('booking'));
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('cancel', $booking);

        $this->bookings->cancel($booking, $request->user());

        return redirect()->route('bookings.show', $booking)
            ->with('success', "Pemesanan {$booking->code} dibatalkan.");
    }
}

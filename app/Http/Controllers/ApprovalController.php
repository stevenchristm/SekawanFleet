<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Http\Requests\ApprovalActionRequest;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalService $approvals) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'history' ? 'history' : 'pending';
        $term = trim((string) $request->query('q', ''));

        $base = BookingApproval::query()
            ->where('approver_id', $request->user()->id)
            ->with(['booking.vehicle', 'booking.driver', 'booking.approvals.approver']);

        $counts = [
            'pending' => (clone $base)->where('status', ApprovalStatus::Pending)->count(),
            'history' => (clone $base)->whereIn('status', [ApprovalStatus::Approved, ApprovalStatus::Rejected])->count(),
        ];

        // Pencarian: kode booking, pemohon, tujuan, atau plat kendaraan
        $base->when($term !== '', fn ($q) => $q->whereHas('booking', fn ($b) => $b
            ->where('code', 'ilike', "%{$term}%")
            ->orWhere('requester_name', 'ilike', "%{$term}%")
            ->orWhere('destination', 'ilike', "%{$term}%")
            ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'ilike', "%{$term}%"))));

        $approvals = $tab === 'pending'
            ? (clone $base)->where('status', ApprovalStatus::Pending)
                // Urut berdasarkan jadwal paling dekat (subquery, tanpa join agar kolom tidak ambigu)
                ->orderBy(Booking::select('start_at')->whereColumn('bookings.id', 'booking_approvals.booking_id'))
                ->paginate(10)->withQueryString()
            : (clone $base)->whereIn('status', [ApprovalStatus::Approved, ApprovalStatus::Rejected])
                ->latest('acted_at')
                ->paginate(10)->withQueryString();

        return view('approvals.index', [
            'tab' => $tab,
            'term' => $term,
            'approvals' => $approvals,
            'counts' => $counts,
        ]);
    }

    public function approve(ApprovalActionRequest $request, BookingApproval $approval): RedirectResponse
    {
        Gate::authorize('act', $approval);

        $this->approvals->approve($approval, $request->user(), $request->validated('note'));

        $booking = $approval->booking->refresh();

        return back()->with('success', $booking->status === BookingStatus::Approved
            ? "Pemesanan {$booking->code} disetujui penuh dan siap digunakan."
            : "Pemesanan {$booking->code} disetujui, diteruskan ke level {$booking->current_level}.");
    }

    public function reject(ApprovalActionRequest $request, BookingApproval $approval): RedirectResponse
    {
        Gate::authorize('act', $approval);

        $this->approvals->reject($approval, $request->user(), $request->validated('note'));

        return back()->with('success', "Pemesanan {$approval->booking->code} ditolak.");
    }
}

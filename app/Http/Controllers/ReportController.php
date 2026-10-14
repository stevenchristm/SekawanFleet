<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\VehicleType;
use App\Exports\BookingReportExport;
use App\Http\Requests\ReportFilterRequest;
use App\Models\Location;
use App\Services\ReportService;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(ReportFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('reports.index', [
            'filters' => $filters,
            'summary' => $this->reports->summary($filters),
            'bookings' => $this->reports->query($filters)->paginate(20)->withQueryString(),
            'statuses' => BookingStatus::cases(),
            'types' => VehicleType::cases(),
            'locations' => Location::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(ReportFilterRequest $request): BinaryFileResponse
    {
        $filters = $request->validated();
        $fileName = "laporan-pemesanan_{$filters['from']}_{$filters['to']}.xlsx";

        activity('report')
            ->causedBy($request->user())
            ->withProperties(['filters' => $filters, 'file' => $fileName])
            ->log('Export laporan pemesanan ke Excel');

        return Excel::download(new BookingReportExport($filters, $this->reports), $fileName);
    }
}

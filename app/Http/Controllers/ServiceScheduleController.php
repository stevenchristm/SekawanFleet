<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteServiceScheduleRequest;
use App\Http\Requests\StoreServiceScheduleRequest;
use App\Models\ServiceSchedule;
use App\Models\Vehicle;
use App\Services\ServiceScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceScheduleController extends Controller
{
    public function __construct(private ServiceScheduleService $services) {}

    public function store(StoreServiceScheduleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $service = $this->services->schedule($vehicle, $request->validated(), $request->user());

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', "{$service->type->label()} dijadwalkan pada {$service->scheduled_date->format('d/m/Y')}.");
    }

    public function complete(CompleteServiceScheduleRequest $request, ServiceSchedule $service): RedirectResponse
    {
        $service = $this->services->complete($service, $request->validated(), $request->user());

        return redirect()->route('vehicles.show', $service->vehicle_id)
            ->with('success', "{$service->type->label()} {$service->vehicle->plate_number} ditandai selesai.");
    }

    public function cancel(Request $request, ServiceSchedule $service): RedirectResponse
    {
        $this->services->cancel($service, $request->user());

        return redirect()->route('vehicles.show', $service->vehicle_id)
            ->with('success', 'Jadwal servis dibatalkan.');
    }
}

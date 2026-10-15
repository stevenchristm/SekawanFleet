<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ServiceStatus;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Region;
use App\Models\ServiceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ServiceScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Vehicle $vehicle;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-15 10:00:00'));

        $region = Region::create(['name' => 'Sulteng']);
        $location = Location::create(['region_id' => $region->id, 'name' => 'Site Morowali', 'type' => 'mine']);
        $this->admin = User::factory()->admin()->create();
        $this->vehicle = Vehicle::create([
            'location_id' => $location->id, 'plate_number' => 'DN 1 A', 'brand_model' => 'Hilux',
            'type' => 'passenger', 'ownership' => 'owned', 'status' => 'available',
        ]);
        $this->driver = Driver::create(['location_id' => $location->id, 'name' => 'Joko', 'license_number' => 'SIM-1']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_schedules_service_and_it_is_logged(): void
    {
        $this->actingAs($this->admin)->post(route('services.store', $this->vehicle), [
            'type' => 'routine', 'scheduled_date' => '2026-10-20', 'odometer_due' => 50000,
            'workshop' => 'Bengkel Pool', 'description' => 'Ganti oli',
        ])->assertRedirect(route('vehicles.show', $this->vehicle))->assertSessionHasNoErrors();

        $service = ServiceSchedule::sole();
        $this->assertSame(ServiceStatus::Scheduled, $service->status);
        $this->assertSame('2026-10-20', $service->scheduled_date->toDateString());
        $this->assertSame('soon', $service->dueState());
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'service', 'subject_id' => $service->id,
            'description' => 'Servis Berkala DN 1 A dijadwalkan 20/10/2026',
        ]);
    }

    public function test_cannot_schedule_service_on_a_booked_day_or_twice_on_the_same_day(): void
    {
        $booking = $this->booking(BookingStatus::Approved, '2026-10-20 08:00', '2026-10-20 16:00');
        $post = fn (string $date) => $this->actingAs($this->admin)
            ->post(route('services.store', $this->vehicle), ['type' => 'routine', 'scheduled_date' => $date]);

        $post('2026-10-20')->assertSessionHasErrorsIn('schedule', 'scheduled_date');
        $this->assertSame(0, ServiceSchedule::count());
        $this->assertStringContainsString($booking->code, session('errors')->getBag('schedule')->first('scheduled_date'));

        $post('2026-10-21')->assertSessionHasNoErrors();
        $post('2026-10-21')->assertSessionHasErrorsIn('schedule', 'scheduled_date');
        $post('2026-10-10')->assertSessionHasErrorsIn('schedule', 'scheduled_date'); // masa lalu
        $this->assertSame(1, ServiceSchedule::count());
    }

    public function test_booking_is_refused_on_a_scheduled_service_day(): void
    {
        $this->scheduled('2026-10-22');
        [$a1, $a2] = User::factory()->approver()->count(2)->create();

        $this->actingAs($this->admin)->post(route('bookings.store'), [
            'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id,
            'requester_name' => 'Andi', 'purpose' => 'Inspeksi', 'destination' => 'Pit B',
            'start_at' => '2026-10-21 08:00', 'end_at' => '2026-10-22 09:00',
            'approvers' => [$a1->id, $a2->id],
        ])->assertSessionHasErrors('vehicle_id');

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, Vehicle::availableBetween('2026-10-21 08:00', '2026-10-22 09:00')->count());
        $this->assertSame(1, Vehicle::availableBetween('2026-10-23 08:00', '2026-10-23 12:00')->count());
    }

    public function test_complete_service_records_result_and_odometer_cannot_go_backwards(): void
    {
        $previous = $this->booking(BookingStatus::Completed, '2026-10-10 08:00', '2026-10-10 12:00');
        VehicleUsage::create(['booking_id' => $previous->id, 'start_odometer' => 41000, 'end_odometer' => 41200, 'fuel_liters' => 20, 'returned_at' => '2026-10-10 12:00']);
        $service = $this->scheduled('2026-10-15');

        $patch = fn (array $data) => $this->actingAs($this->admin)->patch(route('services.complete', $service), $data + [
            'completed_date' => '2026-10-15', 'odometer_at_service' => 41250, '_service_id' => $service->id,
        ]);

        $patch(['odometer_at_service' => 41100])->assertSessionHasErrorsIn('completeService', 'odometer_at_service');
        $patch(['completed_date' => '2026-10-16'])->assertSessionHasErrorsIn('completeService', 'completed_date');
        $this->assertSame(ServiceStatus::Scheduled, $service->refresh()->status);

        $patch(['cost' => 850000, 'notes' => 'Ganti oli & filter'])
            ->assertRedirect(route('vehicles.show', $this->vehicle))->assertSessionHasNoErrors();

        $service->refresh();
        $this->assertSame(ServiceStatus::Completed, $service->status);
        $this->assertSame(41250, $service->odometer_at_service);
        $this->assertEquals(850000, $service->cost);
        $this->assertSame($this->admin->id, $service->completed_by);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'service', 'description' => 'Servis Berkala DN 1 A selesai dikerjakan']);

        // Sudah selesai: tidak bisa diselesaikan / dibatalkan lagi
        $patch([])->assertSessionHasErrors('service');
        $this->actingAs($this->admin)->patch(route('services.cancel', $service))->assertSessionHasErrors('service');
    }

    public function test_cancel_frees_the_day_for_booking(): void
    {
        $service = $this->scheduled('2026-10-22');

        $this->actingAs($this->admin)->patch(route('services.cancel', $service))->assertSessionHasNoErrors();

        $this->assertSame(ServiceStatus::Cancelled, $service->refresh()->status);
        $this->assertSame(1, Vehicle::availableBetween('2026-10-22 08:00', '2026-10-22 12:00')->count());
        $this->assertDatabaseHas('activity_log', ['log_name' => 'service', 'description' => 'Servis Berkala DN 1 A tanggal 22/10/2026 dibatalkan']);
    }

    public function test_vehicle_pages_show_usage_and_service_state(): void
    {
        $previous = $this->booking(BookingStatus::Completed, '2026-10-10 08:00', '2026-10-10 12:00');
        VehicleUsage::create(['booking_id' => $previous->id, 'start_odometer' => 41000, 'end_odometer' => 41200, 'fuel_liters' => 20, 'returned_at' => '2026-10-10 12:00']);
        $this->scheduled('2026-10-12'); // terlambat 3 hari

        $this->actingAs($this->admin)->get(route('vehicles.index'))
            ->assertOk()->assertSee('DN 1 A')->assertSee('41.200 km')->assertSee('Terlambat 3 hari');

        $this->actingAs($this->admin)->get(route('vehicles.index', ['service' => 'overdue']))->assertOk()->assertSee('DN 1 A');
        $this->actingAs($this->admin)->get(route('vehicles.index', ['service' => 'soon']))->assertOk()->assertDontSee('Hilux');

        $this->actingAs($this->admin)->get(route('vehicles.show', $this->vehicle))
            ->assertOk()->assertSee('Jadwal Servis')->assertSee($previous->code)->assertSee('200 km')->assertSee('10,0 km/L');

        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee('Jadwal Servis Kendaraan');
    }

    public function test_approver_cannot_access_vehicle_pages_or_manage_services(): void
    {
        $approver = User::factory()->approver()->create();
        $service = $this->scheduled('2026-10-22');

        $this->actingAs($approver)->get(route('vehicles.index'))->assertForbidden();
        $this->actingAs($approver)->get(route('vehicles.show', $this->vehicle))->assertForbidden();
        $this->actingAs($approver)->post(route('services.store', $this->vehicle), ['type' => 'routine', 'scheduled_date' => '2026-10-25'])->assertForbidden();
        $this->actingAs($approver)->patch(route('services.cancel', $service))->assertForbidden();
        $this->actingAs($approver)->get(route('dashboard'))->assertOk()->assertDontSee('Jadwal Servis Kendaraan');
    }

    private function scheduled(string $date): ServiceSchedule
    {
        return ServiceSchedule::create([
            'vehicle_id' => $this->vehicle->id, 'type' => 'routine', 'scheduled_date' => $date,
            'status' => 'scheduled', 'created_by' => $this->admin->id,
        ]);
    }

    private int $slot = 0;

    private function booking(BookingStatus $status, string $start, string $end): Booking
    {
        $driver = $this->slot++ === 0 ? $this->driver : Driver::create([
            'location_id' => $this->driver->location_id, 'name' => 'Driver '.$this->slot, 'license_number' => 'SIM-X'.$this->slot,
        ]);

        return Booking::create([
            'vehicle_id' => $this->vehicle->id, 'driver_id' => $driver->id, 'created_by' => $this->admin->id,
            'requester_name' => 'Andi', 'purpose' => 'Inspeksi', 'destination' => 'Pit B',
            'start_at' => $start, 'end_at' => $end, 'status' => $status,
        ]);
    }
}

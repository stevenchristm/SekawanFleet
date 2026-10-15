<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Region;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Vehicle $vehicle;
    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-15 15:00:00'));

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

    public function test_admin_records_return_and_booking_becomes_completed(): void
    {
        $booking = $this->booking(BookingStatus::Approved, '2026-10-15 08:00', '2026-10-15 12:00');

        $this->actingAs($this->admin)->post(route('bookings.complete', $booking), [
            'start_odometer' => 42150, 'end_odometer' => 42280, 'fuel_liters' => 18.5,
            'returned_at' => '2026-10-15 12:30', 'notes' => 'Kondisi baik',
        ])->assertRedirect(route('bookings.show', $booking))->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::Completed, $booking->refresh()->status);
        $usage = $booking->usage;
        $this->assertSame(130, $usage->distance);
        $this->assertEquals(18.5, $usage->fuel_liters);
        $this->assertSame('Kondisi baik', $usage->notes);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'booking', 'subject_id' => $booking->id, 'description' => "Pengembalian kendaraan {$booking->code} dicatat (130 km)"]);

        $this->actingAs($this->admin)->get(route('bookings.show', $booking))
            ->assertOk()->assertSee('Riwayat Pemakaian')->assertSee('130 km')->assertDontSee('Catat Pengembalian');
    }

    public function test_return_form_prefills_last_odometer(): void
    {
        $previous = $this->booking(BookingStatus::Completed, '2026-10-10 08:00', '2026-10-10 12:00');
        VehicleUsage::create(['booking_id' => $previous->id, 'start_odometer' => 1000, 'end_odometer' => 1250, 'returned_at' => '2026-10-10 12:00']);
        $booking = $this->booking(BookingStatus::Approved, '2026-10-15 08:00', '2026-10-15 12:00');

        $this->actingAs($this->admin)->get(route('bookings.show', $booking))
            ->assertOk()->assertSee('Catat Pengembalian')->assertSee('value="1250"', false);
    }

    public function test_validation_rules(): void
    {
        $booking = $this->booking(BookingStatus::Approved, '2026-10-15 08:00', '2026-10-15 12:00');
        $post = fn (array $data) => $this->actingAs($this->admin)->post(route('bookings.complete', $booking), $data + [
            'start_odometer' => 100, 'end_odometer' => 200, 'returned_at' => '2026-10-15 12:00',
        ]);

        $post(['end_odometer' => 50])->assertSessionHasErrors('end_odometer');
        $post(['returned_at' => '2026-10-15 07:00'])->assertSessionHasErrors('returned_at');  // sebelum mulai
        $post(['returned_at' => '2026-10-16 07:00'])->assertSessionHasErrors('returned_at');  // masa depan
        $post(['fuel_liters' => -1])->assertSessionHasErrors('fuel_liters');

        $this->assertSame(BookingStatus::Approved, $booking->refresh()->status);
        $this->assertSame(0, VehicleUsage::count());
    }

    public function test_only_started_approved_bookings_can_be_completed(): void
    {
        $data = ['start_odometer' => 100, 'end_odometer' => 200, 'returned_at' => '2026-10-15 14:00'];

        $pending = $this->booking(BookingStatus::Pending, '2026-10-15 08:00', '2026-10-15 09:00');
        $future = $this->booking(BookingStatus::Approved, '2026-10-16 08:00', '2026-10-16 12:00');
        $done = $this->booking(BookingStatus::Completed, '2026-10-14 08:00', '2026-10-14 12:00');

        foreach ([$pending, $future, $done] as $booking) {
            $this->actingAs($this->admin)->post(route('bookings.complete', $booking), $data)->assertForbidden();
        }

        $this->assertSame(0, VehicleUsage::count());
    }

    public function test_approver_cannot_record_return(): void
    {
        $booking = $this->booking(BookingStatus::Approved, '2026-10-15 08:00', '2026-10-15 12:00');

        $this->actingAs(User::factory()->approver()->create())->post(route('bookings.complete', $booking), [
            'start_odometer' => 100, 'end_odometer' => 200, 'returned_at' => '2026-10-15 12:00',
        ])->assertForbidden();
    }

    private int $slot = 0;

    private function booking(BookingStatus $status, string $start, string $end): Booking
    {
        // Driver berbeda per booking supaya constraint anti-bentrok tidak terpicu
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

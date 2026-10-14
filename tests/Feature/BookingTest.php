<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Region;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $approver1;
    private User $approver2;
    private Vehicle $vehicle;
    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $region = Region::create(['name' => 'Sulawesi Tengah']);
        $location = Location::create(['region_id' => $region->id, 'name' => 'Tambang Site 1', 'type' => 'mine']);

        $this->admin = User::factory()->admin()->create();
        $this->approver1 = User::factory()->approver()->create();
        $this->approver2 = User::factory()->approver()->create();
        $this->vehicle = $this->makeVehicle($location, 'DN 1001 AB');
        $this->driver = $this->makeDriver($location, 'SIM-1');
    }

    public function test_admin_can_create_booking_with_sequential_approvals(): void
    {
        $response = $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload());

        $booking = Booking::sole();
        $response->assertRedirect(route('bookings.show', $booking));

        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(1, $booking->current_level);
        $this->assertSame($this->admin->id, $booking->created_by);
        $this->assertMatchesRegularExpression('/^BK-\d{6}-0001$/', $booking->code);

        $approvals = $booking->approvals()->get();
        $this->assertCount(2, $approvals);
        $this->assertSame([1, 2], $approvals->pluck('level')->all());
        $this->assertSame([$this->approver1->id, $this->approver2->id], $approvals->pluck('approver_id')->all());
        $this->assertSame(ApprovalStatus::Pending, $approvals[0]->status);
        $this->assertSame(ApprovalStatus::Waiting, $approvals[1]->status);
    }

    public function test_booking_supports_more_than_two_levels(): void
    {
        $approver3 = User::factory()->approver()->create();

        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload([
            'approvers' => [$this->approver1->id, $this->approver2->id, $approver3->id],
        ]))->assertSessionHasNoErrors();

        $this->assertSame([1, 2, 3], Booking::sole()->approvals()->pluck('level')->all());
    }

    public function test_overlapping_vehicle_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload());

        $otherDriver = $this->makeDriver($this->vehicle->location, 'SIM-2');

        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload([
            'driver_id' => $otherDriver->id,
            'start_at' => $this->at(10)->toDateTimeString(), // overlap 08:00-12:00
            'end_at' => $this->at(14)->toDateTimeString(),
        ]))->assertSessionHasErrors('vehicle_id');

        $this->assertSame(1, Booking::count());
    }

    public function test_overlapping_driver_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload());

        $otherVehicle = $this->makeVehicle($this->vehicle->location, 'DN 1002 AB');

        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload([
            'vehicle_id' => $otherVehicle->id,
        ]))->assertSessionHasErrors('driver_id');

        $this->assertSame(1, Booking::count());
    }

    public function test_back_to_back_booking_is_allowed(): void
    {
        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload());

        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload([
            'start_at' => $this->at(12)->toDateTimeString(), // tepat saat booking pertama selesai
            'end_at' => $this->at(15)->toDateTimeString(),
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, Booking::count());
    }

    public function test_cancelled_booking_frees_the_slot(): void
    {
        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload());
        $first = Booking::sole();

        $this->actingAs($this->admin)->patch(route('bookings.cancel', $first))
            ->assertRedirect(route('bookings.show', $first));

        $first->refresh();
        $this->assertSame(BookingStatus::Cancelled, $first->status);
        $this->assertSame(
            [ApprovalStatus::Skipped, ApprovalStatus::Skipped],
            $first->approvals()->get()->pluck('status')->all()
        );

        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload())
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Booking::count());
    }

    public function test_cancelled_booking_cannot_be_cancelled_again(): void
    {
        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload());
        $booking = Booking::sole();
        $this->actingAs($this->admin)->patch(route('bookings.cancel', $booking));

        $this->actingAs($this->admin)->patch(route('bookings.cancel', $booking))->assertForbidden();
    }

    public function test_approvers_must_be_at_least_two_distinct_approver_users(): void
    {
        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload([
            'approvers' => [$this->approver1->id],
        ]))->assertSessionHasErrors('approvers');

        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload([
            'approvers' => [$this->approver1->id, $this->approver1->id],
        ]))->assertSessionHasErrors('approvers.0');

        // Admin bukan approver
        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload([
            'approvers' => [$this->approver1->id, $this->admin->id],
        ]))->assertSessionHasErrors('approvers.1');

        $this->assertSame(0, Booking::count());
    }

    public function test_invalid_schedule_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload([
            'start_at' => now()->subDay()->toDateTimeString(),
            'end_at' => now()->subDay()->addHour()->toDateTimeString(),
        ]))->assertSessionHasErrors('start_at');

        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload([
            'end_at' => $this->at(7)->toDateTimeString(), // sebelum start 08:00
        ]))->assertSessionHasErrors('end_at');

        $this->assertSame(0, Booking::count());
    }

    public function test_approver_cannot_create_booking(): void
    {
        $this->actingAs($this->approver1)->post(route('bookings.store'), $this->payload())->assertForbidden();
        $this->assertSame(0, Booking::count());
    }

    public function test_only_assigned_approver_can_view_booking(): void
    {
        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload());
        $booking = Booking::sole();
        $outsider = User::factory()->approver()->create();

        $this->actingAs($this->admin)->get(route('bookings.show', $booking))->assertOk()->assertSee($booking->code);
        $this->actingAs($this->approver2)->get(route('bookings.show', $booking))->assertOk();
        $this->actingAs($outsider)->get(route('bookings.show', $booking))->assertForbidden();
    }

    public function test_booking_pages_render(): void
    {
        $this->actingAs($this->admin)->post(route('bookings.store'), $this->payload());

        $this->actingAs($this->admin)->get(route('bookings.index'))->assertOk()->assertSee('DN 1001 AB');
        $this->actingAs($this->admin)->get(route('bookings.index', ['status' => 'pending', 'q' => 'Budi']))->assertOk();
        $this->actingAs($this->admin)->get(route('bookings.create'))->assertOk()->assertSee($this->approver1->name);
    }

    public function test_database_exclusion_constraint_blocks_overlap_as_last_line_of_defense(): void
    {
        $attributes = [
            'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id,
            'created_by' => $this->admin->id, 'requester_name' => 'X', 'purpose' => 'X',
            'destination' => 'X', 'start_at' => $this->at(8), 'end_at' => $this->at(12),
            'status' => BookingStatus::Pending,
        ];
        Booking::create($attributes);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('no_vehicle_overlap');

        // Langsung lewat model, melewati cek aplikasi
        Booking::create([...$attributes, 'driver_id' => $this->makeDriver($this->vehicle->location, 'SIM-9')->id,
            'start_at' => $this->at(9), 'end_at' => $this->at(10)]);
    }

    // ---------------------------------------------------------------- helpers

    private function at(int $hour): Carbon
    {
        return now()->addDays(3)->setTime($hour, 0);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'requester_name' => 'Budi Santoso',
            'purpose' => 'Inspeksi site',
            'destination' => 'Tambang Site 1',
            'start_at' => $this->at(8)->toDateTimeString(),
            'end_at' => $this->at(12)->toDateTimeString(),
            'approvers' => [$this->approver1->id, $this->approver2->id],
        ], $overrides);
    }

    private function makeVehicle(Location $location, string $plate): Vehicle
    {
        return Vehicle::create([
            'location_id' => $location->id, 'plate_number' => $plate, 'brand_model' => 'Toyota Hilux',
            'type' => 'passenger', 'ownership' => 'owned', 'capacity' => 5, 'status' => 'available',
        ]);
    }

    private function makeDriver(Location $location, string $license): Driver
    {
        return Driver::create([
            'location_id' => $location->id, 'name' => 'Driver '.$license,
            'phone' => '0812000000', 'license_number' => $license, 'is_active' => true,
        ]);
    }
}

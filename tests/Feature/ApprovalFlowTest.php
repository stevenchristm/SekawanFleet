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
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $l1;

    private User $l2;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $region = Region::create(['name' => 'Sulawesi Tengah']);
        $location = Location::create(['region_id' => $region->id, 'name' => 'Tambang Site 1', 'type' => 'mine']);
        $vehicle = Vehicle::create([
            'location_id' => $location->id, 'plate_number' => 'DN 1001 AB', 'brand_model' => 'Toyota Hilux',
            'type' => 'passenger', 'ownership' => 'owned', 'capacity' => 5, 'status' => 'available',
        ]);
        $driver = Driver::create([
            'location_id' => $location->id, 'name' => 'Joko', 'license_number' => 'SIM-1', 'is_active' => true,
        ]);

        $this->admin = User::factory()->admin()->create();
        $this->l1 = User::factory()->approver()->create(['name' => 'Budi Supervisor']);
        $this->l2 = User::factory()->approver()->create(['name' => 'Sari Manager']);

        $this->booking = app(BookingService::class)->create([
            'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
            'requester_name' => 'Andi', 'purpose' => 'Inspeksi', 'destination' => 'Site 1',
            'start_at' => now()->addDay()->setTime(8, 0)->toDateTimeString(),
            'end_at' => now()->addDay()->setTime(12, 0)->toDateTimeString(),
            'approvers' => [$this->l1->id, $this->l2->id],
        ], $this->admin);
    }

    public function test_full_approval_flow_level_1_then_level_2(): void
    {
        $this->actingAs($this->l1)->post(route('approvals.approve', $this->level(1)), ['note' => 'OK dari supervisor'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->booking->refresh();
        $this->assertSame(BookingStatus::Pending, $this->booking->status);
        $this->assertSame(2, $this->booking->current_level);
        $this->assertSame(ApprovalStatus::Approved, $this->level(1)->status);
        $this->assertNotNull($this->level(1)->acted_at);
        $this->assertSame('OK dari supervisor', $this->level(1)->note);
        $this->assertSame(ApprovalStatus::Pending, $this->level(2)->status);

        $this->actingAs($this->l2)->post(route('approvals.approve', $this->level(2)))
            ->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::Approved, $this->booking->refresh()->status);
        $this->assertSame(ApprovalStatus::Approved, $this->level(2)->status);
    }

    public function test_rejection_at_level_1_rejects_booking_and_skips_next_levels(): void
    {
        $this->actingAs($this->l1)->post(route('approvals.reject', $this->level(1)), ['note' => 'Kendaraan dibutuhkan untuk prioritas lain'])
            ->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::Rejected, $this->booking->refresh()->status);
        $this->assertSame(ApprovalStatus::Rejected, $this->level(1)->status);
        $this->assertSame(ApprovalStatus::Skipped, $this->level(2)->status);

        // Level 2 tidak bisa lagi memproses
        $this->actingAs($this->l2)->post(route('approvals.approve', $this->level(2)))->assertForbidden();
    }

    public function test_rejection_at_last_level_rejects_booking(): void
    {
        $this->actingAs($this->l1)->post(route('approvals.approve', $this->level(1)));
        $this->actingAs($this->l2)->post(route('approvals.reject', $this->level(2)), ['note' => 'Tidak sesuai anggaran']);

        $this->assertSame(BookingStatus::Rejected, $this->booking->refresh()->status);
        $this->assertSame(ApprovalStatus::Approved, $this->level(1)->status);
        $this->assertSame(ApprovalStatus::Rejected, $this->level(2)->status);
    }

    public function test_rejected_booking_frees_vehicle_slot(): void
    {
        $this->actingAs($this->l1)->post(route('approvals.reject', $this->level(1)), ['note' => 'Ditolak']);

        app(BookingService::class)->ensureNoConflict(
            $this->booking->vehicle_id, $this->booking->driver_id, $this->booking->start_at, $this->booking->end_at
        );
        $this->assertTrue(true); // tidak melempar ValidationException
    }

    public function test_level_2_cannot_act_before_level_1(): void
    {
        $this->actingAs($this->l2)->post(route('approvals.approve', $this->level(2)))->assertForbidden();

        $this->assertSame(ApprovalStatus::Waiting, $this->level(2)->status);
        $this->assertSame(1, $this->booking->refresh()->current_level);
    }

    public function test_other_users_cannot_act_on_someone_elses_approval(): void
    {
        $outsider = User::factory()->approver()->create();

        $this->actingAs($outsider)->post(route('approvals.approve', $this->level(1)))->assertForbidden();
        $this->actingAs($this->l2)->post(route('approvals.approve', $this->level(1)))->assertForbidden();
        // Admin tidak masuk grup route approver
        $this->actingAs($this->admin)->post(route('approvals.approve', $this->level(1)))->assertForbidden();

        $this->assertSame(ApprovalStatus::Pending, $this->level(1)->status);
    }

    public function test_approval_cannot_be_processed_twice(): void
    {
        $this->actingAs($this->l1)->post(route('approvals.approve', $this->level(1)));
        $this->actingAs($this->l1)->post(route('approvals.reject', $this->level(1)), ['note' => 'Berubah pikiran'])
            ->assertForbidden();

        $this->assertSame(ApprovalStatus::Approved, $this->level(1)->status);
    }

    public function test_cancelled_booking_cannot_be_approved(): void
    {
        $this->actingAs($this->admin)->patch(route('bookings.cancel', $this->booking));

        $this->actingAs($this->l1)->post(route('approvals.approve', $this->level(1)))->assertForbidden();
        $this->assertSame(BookingStatus::Cancelled, $this->booking->refresh()->status);
    }

    public function test_reject_requires_a_reason(): void
    {
        $this->actingAs($this->l1)->post(route('approvals.reject', $this->level(1)), ['note' => ''])
            ->assertSessionHasErrors('note');

        $this->assertSame(ApprovalStatus::Pending, $this->level(1)->status);
        $this->assertSame(BookingStatus::Pending, $this->booking->refresh()->status);
    }

    public function test_actions_are_recorded_in_activity_log(): void
    {
        $this->actingAs($this->l1)->post(route('approvals.approve', $this->level(1)));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'approval',
            'subject_type' => Booking::class,
            'subject_id' => $this->booking->id,
            'causer_id' => $this->l1->id,
        ]);
    }

    public function test_inbox_shows_only_my_pending_approvals(): void
    {
        $this->actingAs($this->l1)->get(route('approvals.index'))
            ->assertOk()->assertSee($this->booking->code)->assertSee('Setujui');

        // L2 masih waiting: belum muncul di inbox-nya
        $this->actingAs($this->l2)->get(route('approvals.index'))
            ->assertOk()->assertDontSee($this->booking->code);

        $this->actingAs($this->l1)->post(route('approvals.approve', $this->level(1)));

        $this->actingAs($this->l2)->get(route('approvals.index'))->assertSee($this->booking->code);
        $this->actingAs($this->l1)->get(route('approvals.index', ['tab' => 'history']))->assertSee($this->booking->code);
    }

    public function test_booking_detail_shows_actions_only_on_my_turn(): void
    {
        $this->actingAs($this->l1)->get(route('bookings.show', $this->booking))
            ->assertOk()->assertSee('menunggu persetujuan Anda', false);

        $this->actingAs($this->l2)->get(route('bookings.show', $this->booking))
            ->assertOk()->assertDontSee('menunggu persetujuan Anda', false);
    }

    private function level(int $level)
    {
        return $this->booking->approvals()->where('level', $level)->firstOrFail();
    }
}

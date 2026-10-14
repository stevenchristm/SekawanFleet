<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Location;
use App\Models\Region;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['email' => 'admin@nikel.test']);
    }

    public function test_login_logout_and_failed_login_are_logged(): void
    {
        $this->post('/login', ['email' => 'admin@nikel.test', 'password' => 'salah'])->assertSessionHasErrors();
        $this->post('/login', ['email' => 'admin@nikel.test', 'password' => 'password']);
        $this->post('/logout');

        $events = Activity::where('log_name', 'auth')->orderBy('id')->pluck('event')->all();
        $this->assertSame(['login_failed', 'login', 'logout'], $events);

        $failed = Activity::where('event', 'login_failed')->sole();
        $this->assertSame('admin@nikel.test', $failed->properties['email']);
        $this->assertArrayNotHasKey('password', $failed->properties->toArray());
        $this->assertSame($this->admin->id, Activity::where('event', 'login')->sole()->causer_id);
    }

    public function test_business_actions_appear_on_log_page(): void
    {
        $region = Region::create(['name' => 'Sulteng']);
        $location = Location::create(['region_id' => $region->id, 'name' => 'Site 1', 'type' => 'mine']);
        $vehicle = Vehicle::create([
            'location_id' => $location->id, 'plate_number' => 'DN 1 A', 'brand_model' => 'Hilux',
            'type' => 'passenger', 'ownership' => 'owned', 'status' => 'available',
        ]);
        $driver = Driver::create(['location_id' => $location->id, 'name' => 'Joko', 'license_number' => 'SIM-1']);
        [$l1, $l2] = User::factory()->approver()->count(2)->create();

        $this->actingAs($this->admin);
        $booking = app(BookingService::class)->create([
            'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'requester_name' => 'Andi',
            'purpose' => 'Inspeksi', 'destination' => 'Site 1',
            'start_at' => now()->addDay()->setTime(8, 0)->toDateTimeString(),
            'end_at' => now()->addDay()->setTime(12, 0)->toDateTimeString(),
            'approvers' => [$l1->id, $l2->id],
        ], $this->admin);

        $this->actingAs($l1)->post(route('approvals.approve', $booking->approvals()->where('level', 1)->first()));

        $this->assertDatabaseHas('activity_log', ['log_name' => 'fleet', 'description' => 'Kendaraan DN 1 A ditambahkan']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'booking', 'description' => "Pemesanan {$booking->code} dibuat", 'causer_id' => $this->admin->id]);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'approval', 'causer_id' => $l1->id]);

        $this->actingAs($this->admin)->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee("Pemesanan {$booking->code} dibuat")
            ->assertSee("Menyetujui pemesanan {$booking->code} (level 1)");
    }

    public function test_log_page_filters(): void
    {
        activity('report')->causedBy($this->admin)->log('Export laporan pemesanan ke Excel');
        activity('auth')->causedBy($this->admin)->log('Login');

        $this->actingAs($this->admin)->get(route('activity-logs.index', ['log' => 'report']))
            ->assertOk()->assertSee('Export laporan')->assertDontSee('>Login<', false);

        $this->actingAs($this->admin)->get(route('activity-logs.index', ['q' => 'export']))
            ->assertOk()->assertSee('Export laporan');

        $this->actingAs($this->admin)->get(route('activity-logs.index', ['log' => 'tidak-ada']))
            ->assertSessionHasErrors('log');
    }

    public function test_activities_are_mirrored_to_log_file(): void
    {
        Log::shouldReceive('channel')->with('activity')->once()->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(fn ($msg) => $msg === '[report] Export uji');

        activity('report')->log('Export uji');
    }

    public function test_only_admin_can_view_log(): void
    {
        $approver = User::factory()->approver()->create();
        $this->actingAs($approver)->get(route('activity-logs.index'))->assertForbidden();
    }
}

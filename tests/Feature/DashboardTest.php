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
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Vehicle $car;
    private Vehicle $truck;
    private Driver $driver;
    private int $slot = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Pertengahan bulan supaya "bulan ini" dan "bulan lalu" tidak ambigu
        Carbon::setTestNow(Carbon::parse('2026-10-15 10:00:00'));

        $region = Region::create(['name' => 'Sulawesi Tengah']);
        $location = Location::create(['region_id' => $region->id, 'name' => 'Site 1', 'type' => 'mine']);
        $rental = \App\Models\RentalCompany::create(['name' => 'PT Rental']);

        $this->admin = User::factory()->admin()->create();
        $this->car = Vehicle::create([
            'location_id' => $location->id, 'plate_number' => 'DN 1 A', 'brand_model' => 'Hilux',
            'type' => 'passenger', 'ownership' => 'owned', 'capacity' => 5, 'status' => 'available',
        ]);
        $this->truck = Vehicle::create([
            'location_id' => $location->id, 'rental_company_id' => $rental->id, 'plate_number' => 'DN 2 B',
            'brand_model' => 'Fuso', 'type' => 'cargo', 'ownership' => 'rented', 'capacity' => 8, 'status' => 'available',
        ]);
        $this->driver = Driver::create(['location_id' => $location->id, 'name' => 'Joko', 'license_number' => 'SIM-1']);

        // Bulan ini (Okt): 2 selesai mobil, 1 pending truk, 1 ditolak truk
        $this->booking($this->car, '2026-10-02', BookingStatus::Completed, fuel: 20, km: 100);
        $this->booking($this->car, '2026-10-05', BookingStatus::Completed, fuel: 10, km: 50);
        $this->booking($this->truck, '2026-10-20', BookingStatus::Pending);
        $this->booking($this->truck, '2026-10-21', BookingStatus::Rejected);
        // Bulan lalu (Sep): 1 approved truk
        $this->booking($this->truck, '2026-09-10', BookingStatus::Approved);
        // Di luar periode 3 bulan (Jun)
        $this->booking($this->car, '2026-06-10', BookingStatus::Completed, fuel: 99, km: 999);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_monthly_chart_counts_bookings_by_status_including_empty_months(): void
    {
        $monthly = app(DashboardService::class)->charts(3)['monthly'];

        $this->assertSame(['Agt 2026', 'Sep 2026', 'Okt 2026'], $monthly['labels']);
        $this->assertSame([0, 1, 2], $monthly['used']);
        $this->assertSame([0, 0, 1], $monthly['pending']);
        $this->assertSame([0, 0, 1], $monthly['rejected']);
    }

    public function test_top_vehicles_type_and_fuel_charts(): void
    {
        $charts = app(DashboardService::class)->charts(3);

        $this->assertSame(['DN 1 A', 'DN 2 B'], $charts['topVehicles']['labels']);
        $this->assertSame([2, 1], $charts['topVehicles']['trips']);

        $this->assertSame([2, 0], $charts['byType']['owned']);   // mobil milik sendiri
        $this->assertSame([0, 1], $charts['byType']['rented']);  // truk sewa (approved)

        $this->assertSame([0.0, 0.0, 30.0], $charts['fuel']['liters']);
        $this->assertSame([0, 0, 150], $charts['fuel']['km']);

        $status = array_combine($charts['status']['keys'], $charts['status']['data']);
        $this->assertSame(['pending' => 1, 'approved' => 1, 'rejected' => 1, 'completed' => 2, 'cancelled' => 0], $status);
    }

    public function test_kpis(): void
    {
        $kpis = app(DashboardService::class)->kpis();

        $this->assertSame(4, $kpis['bookings_this_month']);
        $this->assertSame(1, $kpis['pending']);
        $this->assertSame(2, $kpis['vehicles_total']);
    }

    public function test_dashboard_renders_for_admin_and_approver(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('chart-monthly')
            ->assertSee('Pemesanan Terbaru')
            ->assertDontSee('Persetujuan Saya</h3>', false);

        $approver = User::factory()->approver()->create();
        $this->actingAs($approver)->get(route('dashboard', ['months' => 12]))
            ->assertOk()
            ->assertSee('Persetujuan Saya</h3>', false)
            ->assertDontSee('Pemesanan Terbaru');
    }

    public function test_invalid_period_falls_back_to_default(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard', ['months' => 999]))
            ->assertOk()->assertSee('6 bulan terakhir');
    }

    private function booking(Vehicle $vehicle, string $date, BookingStatus $status, ?float $fuel = null, ?int $km = null): void
    {
        // Jam dibuat berbeda supaya tidak kena constraint anti-bentrok
        $start = Carbon::parse($date)->setTime(6 + ($this->slot++ % 10), 0);

        $booking = Booking::create([
            'vehicle_id' => $vehicle->id, 'driver_id' => $this->driver->id, 'created_by' => $this->admin->id,
            'requester_name' => 'X', 'purpose' => 'X', 'destination' => 'X',
            'start_at' => $start, 'end_at' => $start->copy()->addHours(2), 'status' => $status,
        ]);

        if ($fuel !== null) {
            VehicleUsage::create([
                'booking_id' => $booking->id, 'start_odometer' => 1000,
                'end_odometer' => 1000 + $km, 'fuel_liters' => $fuel, 'returned_at' => $booking->end_at,
            ]);
        }
    }
}

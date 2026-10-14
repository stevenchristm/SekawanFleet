<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exports\BookingReportExport;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Region;
use App\Models\RentalCompany;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $approver;
    private Vehicle $car;
    private Vehicle $truck;
    private Location $site;
    private Location $office;
    private Driver $driver;
    private int $slot = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-15 10:00:00'));

        $region = Region::create(['name' => 'Sulteng']);
        $this->site = Location::create(['region_id' => $region->id, 'name' => 'Tambang Site 1', 'type' => 'mine']);
        $this->office = Location::create(['region_id' => $region->id, 'name' => 'Kantor Cabang', 'type' => 'branch_office']);
        $rental = RentalCompany::create(['name' => 'PT Rental Jaya']);

        $this->admin = User::factory()->admin()->create();
        $this->approver = User::factory()->approver()->create(['name' => 'Budi Supervisor']);
        $this->car = Vehicle::create([
            'location_id' => $this->site->id, 'plate_number' => 'DN 1 A', 'brand_model' => 'Hilux',
            'type' => 'passenger', 'ownership' => 'owned', 'capacity' => 5, 'status' => 'available',
        ]);
        $this->truck = Vehicle::create([
            'location_id' => $this->office->id, 'rental_company_id' => $rental->id, 'plate_number' => 'DN 2 B',
            'brand_model' => 'Fuso', 'type' => 'cargo', 'ownership' => 'rented', 'capacity' => 8, 'status' => 'available',
        ]);
        $this->driver = Driver::create(['location_id' => $this->site->id, 'name' => 'Joko', 'license_number' => 'SIM-1']);

        $this->booking($this->car, '2026-10-02', BookingStatus::Completed, km: 120, fuel: 15.5);
        $this->booking($this->car, '2026-10-03', BookingStatus::Rejected);
        $this->booking($this->truck, '2026-10-10', BookingStatus::Approved);
        $this->booking($this->truck, '2026-09-20', BookingStatus::Completed, km: 300, fuel: 40); // bulan lalu
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_report_defaults_to_current_month(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('01/10/2026')
            ->assertSee('31/10/2026')
            ->assertViewHas('summary', fn ($s) => $s['total'] === 3
                && $s['by_status']['completed'] === 1
                && $s['by_status']['rejected'] === 1
                && $s['km'] === 120);
    }

    public function test_report_filters_by_period_status_type_and_location(): void
    {
        $get = fn (array $q) => $this->actingAs($this->admin)->get(route('reports.index', $q))->viewData('summary')['total'];

        $this->assertSame(4, $get(['from' => '2026-09-01', 'to' => '2026-10-31']));
        $this->assertSame(2, $get(['from' => '2026-09-01', 'to' => '2026-10-31', 'status' => 'completed']));
        $this->assertSame(2, $get(['from' => '2026-09-01', 'to' => '2026-10-31', 'type' => 'cargo']));
        $this->assertSame(2, $get(['from' => '2026-09-01', 'to' => '2026-10-31', 'location_id' => $this->site->id]));
        // Batas akhir inklusif sampai akhir hari
        $this->assertSame(1, $get(['from' => '2026-10-02', 'to' => '2026-10-02']));
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index', ['from' => '2025-01-01', 'to' => '2026-10-31']))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHasErrors('to');

        $this->actingAs($this->admin)->get(route('reports.index', ['from' => '2026-10-31', 'to' => '2026-10-01']))
            ->assertSessionHasErrors('to');

        $this->actingAs($this->admin)->get(route('reports.export', ['from' => 'kemarin', 'to' => '2026-10-01']))
            ->assertSessionHasErrors('from');
    }

    public function test_export_downloads_file_and_is_logged(): void
    {
        Excel::fake();

        $this->actingAs($this->admin)
            ->get(route('reports.export', ['from' => '2026-10-01', 'to' => '2026-10-31', 'status' => 'completed']))
            ->assertOk();

        Excel::assertDownloaded('laporan-pemesanan_2026-10-01_2026-10-31.xlsx',
            fn (BookingReportExport $export) => $export->filters['status'] === 'completed');

        $this->assertDatabaseHas('activity_log', ['log_name' => 'report', 'causer_id' => $this->admin->id]);
    }

    public function test_excel_file_contains_booking_rows_and_summary(): void
    {
        $binary = Excel::raw(new BookingReportExport(['from' => '2026-10-01', 'to' => '2026-10-31']), ExcelWriter::XLSX);

        $path = tempnam(sys_get_temp_dir(), 'rpt');
        file_put_contents($path, $binary);

        try {
            $book = IOFactory::load($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame(['Pemesanan', 'Ringkasan'], $book->getSheetNames());

        $rows = $book->getSheetByName('Pemesanan')->toArray();
        $header = $rows[0];
        $this->assertSame('Kode', $header[1]);
        $this->assertContains('Approver L1', $header);
        $this->assertCount(4, $rows); // header + 3 booking Oktober

        $first = array_combine($header, $rows[1]);
        $this->assertSame('DN 1 A', $first['Kendaraan']);
        $this->assertSame('Angkutan Orang', $first['Jenis']);
        $this->assertSame('Selesai', $first['Status']);
        $this->assertSame('Budi Supervisor', $first['Approver L1']);
        $this->assertSame('Disetujui', $first['Keputusan L1']);
        $this->assertEquals(120, $first['Jarak (km)']);
        $this->assertEquals(15.5, $first['BBM (liter)']);

        $truck = array_combine($header, $rows[3]);
        $this->assertSame('PT Rental Jaya', $truck['Perusahaan Sewa']);

        $summary = $book->getSheetByName('Ringkasan')->toArray();
        $flat = array_map(fn ($r) => $r[0], $summary);
        $this->assertContains('Total', $flat);
        $this->assertContains('DN 1 A', $flat);
    }

    public function test_approver_cannot_access_reports(): void
    {
        $this->actingAs($this->approver)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($this->approver)->get(route('reports.export'))->assertForbidden();
    }

    private function booking(Vehicle $vehicle, string $date, BookingStatus $status, ?int $km = null, ?float $fuel = null): void
    {
        $start = Carbon::parse($date)->setTime(7 + ($this->slot++ % 8), 0);

        $booking = Booking::create([
            'vehicle_id' => $vehicle->id, 'driver_id' => $this->driver->id, 'created_by' => $this->admin->id,
            'requester_name' => 'Andi', 'purpose' => 'Inspeksi', 'destination' => 'Site',
            'start_at' => $start, 'end_at' => $start->copy()->addHours(3), 'status' => $status,
        ]);

        BookingApproval::create([
            'booking_id' => $booking->id, 'approver_id' => $this->approver->id, 'level' => 1,
            'status' => $status === BookingStatus::Rejected ? 'rejected' : 'approved', 'acted_at' => $start->copy()->subDay(),
        ]);

        if ($km !== null) {
            VehicleUsage::create([
                'booking_id' => $booking->id, 'start_odometer' => 5000, 'end_odometer' => 5000 + $km,
                'fuel_liters' => $fuel, 'returned_at' => $booking->end_at,
            ]);
        }
    }
}

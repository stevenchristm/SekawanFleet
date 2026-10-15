<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUsage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Data demo pemesanan yang mencakup semua status (pending, approved, rejected,
 * completed, cancelled) dan persetujuan 2-3 level, supaya setiap fitur bisa
 * langsung diperagakan setelah `migrate:fresh --seed`.
 */
class BookingSeeder extends Seeder
{
    private const DESTINATIONS = [
        'Site Morowali - Pit Blok B', 'Site Pomalaa - Smelter', 'Site Kolaka - Jetty',
        'Pelabuhan Morowali', 'Bandara Mutiara SIS Al-Jufri Palu', 'Kantor Cabang Palu',
    ];

    private User $admin;

    private Collection $approvers;

    private Collection $allVehicles;

    private Collection $allDrivers;

    /** Kendaraan & driver yang belum terpakai di "hari" yang sedang dibuat. */
    private Collection $vehicles;

    private Collection $drivers;

    /** Nomor urut kode booking per bulan. */
    private array $seq = [];

    /** Odometer terakhir per kendaraan, supaya riwayat pemakaian naik terus. */
    private array $odometer = [];

    public function run(): void
    {
        $this->admin = User::where('role', 'admin')->firstOrFail();
        $this->approvers = User::where('role', 'approver')->get();
        $this->allVehicles = Vehicle::all();
        $this->allDrivers = Driver::all();
        $this->odometer = $this->allVehicles->mapWithKeys(fn ($v) => [$v->id => rand(15000, 60000)])->all();

        // Riwayat ~6 bulan terakhir (sampai 2 hari lalu).
        for ($d = 180; $d >= 2; $d -= rand(2, 4)) {
            $this->newDay();
            $day = now()->subDays($d)->setTime(rand(7, 10), 0);

            foreach (range(1, rand(1, 3)) as $_) {
                $roll = rand(1, 100);
                match (true) {
                    $roll <= 60 => $this->make($day, 'completed', ['approved', 'approved']),
                    $roll <= 75 => $this->make($day, 'completed', ['approved', 'approved', 'approved']),
                    $roll <= 84 => $this->make($day, 'rejected', ['rejected', 'skipped']),
                    $roll <= 93 => $this->make($day, 'rejected', ['approved', 'rejected']),
                    default => $this->make($day, 'cancelled', ['approved', 'skipped']),
                };
            }
        }

        // Disetujui & waktu mulai sudah lewat -> tombol "Catat Pengembalian" langsung muncul.
        $this->newDay();
        $this->make(now()->subDay()->setTime(8, 0), 'approved', ['approved', 'approved', 'approved'], 8);
        $this->newDay();
        $this->make(now()->subHours(3)->startOfHour(), 'approved', ['approved', 'approved'], 7);

        // Jadwal mendatang: menunggu persetujuan (2-3 level), disetujui, dan dibatalkan.
        foreach (range(1, 4) as $i) {
            $this->newDay();
            $day = now()->addDays($i)->setTime(8, 0);

            $this->make($day, 'pending', ['pending', 'waiting']);
            $this->make($day, 'pending', ['approved', 'pending']);

            match ($i) {
                1 => $this->make($day, 'approved', ['approved', 'approved']),
                2 => $this->make($day, 'pending', ['approved', 'approved', 'pending']),
                3 => $this->make($day, 'pending', ['approved', 'pending', 'waiting']),
                4 => $this->make($day, 'cancelled', ['skipped', 'skipped']),
            };
        }
    }

    private function newDay(): void
    {
        $this->vehicles = $this->allVehicles->shuffle();
        $this->drivers = $this->allDrivers->shuffle();
    }

    /**
     * @param  list<string>  $levels  status approval per level (index 0 = level 1)
     */
    private function make(Carbon $start, string $status, array $levels, ?int $hours = null): void
    {
        $month = $start->format('Ym');
        $this->seq[$month] = ($this->seq[$month] ?? 0) + 1;

        $approvers = $this->approvers->random(count($levels))->values();
        $start = $start->copy();
        $end = $start->copy()->addHours($hours ?? rand(3, 9));

        // Dibuat 2 hari sebelum berangkat (atau 2 hari lalu untuk jadwal mendatang).
        $createdAt = ($start->isFuture() ? now() : $start)->copy()->subDays(2)->setTime(rand(8, 11), rand(0, 59));

        // Level aktif = level pertama yang menunggu / menolak / dilewati; selain itu level terakhir.
        $active = collect($levels)->search(fn ($s) => in_array($s, ['pending', 'rejected', 'skipped'], true));

        $booking = Booking::create([
            'code' => sprintf('BK-%s-%04d', $month, $this->seq[$month]),
            'vehicle_id' => $this->vehicles->shift()->id,
            'driver_id' => $this->drivers->shift()->id,
            'created_by' => $this->admin->id,
            'requester_name' => fake('id_ID')->name(),
            'purpose' => fake()->randomElement(['Inspeksi site', 'Angkut sparepart', 'Antar tamu', 'Survey lokasi']),
            'destination' => fake()->randomElement(self::DESTINATIONS),
            'start_at' => $start,
            'end_at' => $end,
            'status' => $status,
            'current_level' => $active === false ? count($levels) : $active + 1,
        ]);
        $booking->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        foreach ($levels as $i => $st) {
            BookingApproval::create([
                'booking_id' => $booking->id,
                'approver_id' => $approvers[$i]->id,
                'level' => $i + 1,
                'status' => $st,
                'note' => $st === 'rejected' ? 'Jadwal kendaraan dialihkan ke kebutuhan prioritas' : null,
                'acted_at' => in_array($st, ['approved', 'rejected'], true) ? $createdAt->copy()->addHours(3 * ($i + 1)) : null,
            ]);
        }

        if ($status === 'completed') {
            $from = $this->odometer[$booking->vehicle_id];
            $to = $from + rand(20, 250);
            $this->odometer[$booking->vehicle_id] = $to + rand(0, 40); // pemakaian di luar sistem

            VehicleUsage::create([
                'booking_id' => $booking->id,
                'start_odometer' => $from,
                'end_odometer' => $to,
                'fuel_liters' => round(($to - $from) / rand(6, 10), 1),
                'returned_at' => $end,
            ]);
        }
    }
}

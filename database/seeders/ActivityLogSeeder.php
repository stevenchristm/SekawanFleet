<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * Contoh isi log aktivitas untuk 14 hari terakhir, dibangun dari data booking
 * hasil BookingSeeder (dibuat, disetujui/ditolak, dibatalkan, dikembalikan),
 * ditambah login/logout. Halaman Log Aktivitas jadi tidak kosong saat demo.
 *
 * Event model dimatikan supaya entri bertanggal lampau ini tidak ikut
 * ditulis ke file log harian (storage/logs/activity-*.log).
 */
class ActivityLogSeeder extends Seeder
{
    private const CONTEXT = ['ip' => '127.0.0.1', 'user_agent' => 'Seeder (data demo)'];

    public function run(): void
    {
        Activity::withoutEvents(function () {
            $admin = User::where('role', 'admin')->firstOrFail();
            $since = now()->subDays(14);

            $bookings = Booking::with(['approvals.approver', 'usage', 'vehicle'])
                ->where('created_at', '>=', $since)
                ->orderBy('created_at')
                ->get();

            foreach ($bookings as $booking) {
                $this->auth($admin, 'login', 'Login', $booking->created_at->copy()->subMinutes(5));

                $this->log('booking', 'created', "Pemesanan {$booking->code} dibuat", $admin, $booking, $booking->created_at, [
                    'attributes' => [
                        'code' => $booking->code,
                        'vehicle_id' => $booking->vehicle_id,
                        'driver_id' => $booking->driver_id,
                        'destination' => $booking->destination,
                        'start_at' => $booking->start_at->toDateTimeString(),
                        'end_at' => $booking->end_at->toDateTimeString(),
                        'status' => 'pending',
                    ],
                ]);

                foreach ($booking->approvals->whereNotNull('acted_at') as $approval) {
                    $approve = $approval->status->value === 'approved';
                    $last = $approval->level === $booking->approvals->max('level');

                    $this->auth($approval->approver, 'login', 'Login', $approval->acted_at->copy()->subMinutes(3));
                    $this->log('approval', $approve ? 'approved' : 'rejected',
                        ($approve ? 'Menyetujui' : 'Menolak')." pemesanan {$booking->code} (level {$approval->level})",
                        $approval->approver, $booking, $approval->acted_at, [
                            'level' => $approval->level,
                            'decision' => $approval->status->value,
                            'note' => $approval->note,
                            'booking_status' => ! $approve ? 'rejected' : ($last ? 'approved' : 'pending'),
                        ]);
                    $this->auth($approval->approver, 'logout', 'Logout', $approval->acted_at->copy()->addMinutes(2));
                }

                if ($booking->status->value === 'cancelled') {
                    $this->log('booking', 'cancelled', "Pemesanan {$booking->code} dibatalkan", $admin, $booking,
                        $booking->created_at->copy()->addDay());
                }

                if ($usage = $booking->usage) {
                    $this->log('booking', 'completed', "Pengembalian kendaraan {$booking->code} dicatat ({$usage->distance} km)",
                        $admin, $booking, $usage->returned_at->copy()->addMinutes(30), [
                            'start_odometer' => $usage->start_odometer,
                            'end_odometer' => $usage->end_odometer,
                            'fuel_liters' => (float) $usage->fuel_liters,
                        ]);
                }
            }

            // Satu percobaan login gagal sebagai contoh (password tidak pernah dicatat).
            $this->log('auth', 'login_failed', 'Login gagal', null, null, now()->subHours(6),
                self::CONTEXT + ['email' => 'admin@nikel.tes']);
        });
    }

    private function auth(User $user, string $event, string $description, Carbon $at): void
    {
        $this->log('auth', $event, $description, $user, null, $at, self::CONTEXT);
    }

    private function log(string $logName, string $event, string $description, ?User $causer, ?Booking $subject, Carbon $at, array $properties = []): void
    {
        $logger = activity($logName)->event($event)->withProperties($properties)->createdAt($at);

        if ($causer) {
            $logger->causedBy($causer);
        }
        if ($subject) {
            $logger->performedOn($subject);
        }

        $logger->log($description);
    }
}

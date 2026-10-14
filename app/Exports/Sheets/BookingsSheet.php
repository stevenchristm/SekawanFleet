<?php

namespace App\Exports\Sheets;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookingsSheet implements FromQuery, ShouldAutoSize, WithEvents, WithFreezePane, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /** Jumlah level approval yang ditampilkan sebagai kolom terpisah. */
    public const APPROVAL_LEVELS = 3;

    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function title(): string
    {
        return 'Pemesanan';
    }

    public function headings(): array
    {
        $approvals = [];
        foreach (range(1, self::APPROVAL_LEVELS) as $level) {
            $approvals[] = "Approver L{$level}";
            $approvals[] = "Keputusan L{$level}";
        }

        return [
            'No', 'Kode', 'Pemohon', 'Tujuan', 'Keperluan',
            'Kendaraan', 'Merk/Model', 'Jenis', 'Kepemilikan', 'Perusahaan Sewa', 'Lokasi', 'Driver',
            'Mulai', 'Selesai', 'Durasi (jam)', 'Status',
            ...$approvals,
            'Jarak (km)', 'BBM (liter)',
        ];
    }

    private int $row = 0;

    /** @param Booking $booking */
    public function map(mixed $booking): array
    {
        $approvals = [];
        foreach (range(1, self::APPROVAL_LEVELS) as $level) {
            $approval = $booking->approvals->firstWhere('level', $level);
            $approvals[] = $approval?->approver->name ?? '-';
            $approvals[] = $approval?->status->label() ?? '-';
        }

        $usage = $booking->usage;

        return [
            ++$this->row,
            $booking->code,
            $booking->requester_name,
            $booking->destination,
            $booking->purpose,
            $booking->vehicle->plate_number,
            $booking->vehicle->brand_model,
            $booking->vehicle->type->label(),
            $booking->vehicle->ownership->label(),
            $booking->vehicle->rentalCompany?->name ?? '-',
            $booking->vehicle->location->name,
            $booking->driver->name,
            $booking->start_at->format('d/m/Y H:i'),
            $booking->end_at->format('d/m/Y H:i'),
            round($booking->start_at->diffInMinutes($booking->end_at) / 60, 1),
            $booking->status->label(),
            ...$approvals,
            $usage?->end_odometer !== null ? $usage->end_odometer - $usage->start_odometer : null,
            $usage?->fuel_liters !== null ? (float) $usage->fuel_liters : null,
        ];
    }

    public function freezePane(): string
    {
        return 'C2'; // header + kolom No/Kode tetap terlihat
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F2937']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
            },
        ];
    }
}

<?php

namespace App\Exports;

use App\Exports\Sheets\BookingsSheet;
use App\Exports\Sheets\SummarySheet;
use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

/**
 * Laporan pemesanan kendaraan: sheet "Pemesanan" (detail) + "Ringkasan".
 * (Excel v4: export multi-sheet wajib menandai diri sebagai Export.)
 */
class BookingReportExport implements Export, WithMultipleSheets, WithProperties
{
    use Exportable;

    public function __construct(
        public readonly array $filters,
        private readonly ReportService $reports = new ReportService,
    ) {}

    public function sheets(): array
    {
        return [
            new BookingsSheet($this->reports->query($this->filters)),
            new SummarySheet(
                $this->filters,
                $this->reports->summary($this->filters),
                $this->reports->perVehicle($this->filters),
            ),
        ];
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name'),
            'title' => "Laporan Pemesanan Kendaraan {$this->filters['from']} s/d {$this->filters['to']}",
        ];
    }
}

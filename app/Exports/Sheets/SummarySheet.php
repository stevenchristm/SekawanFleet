<?php

namespace App\Exports\Sheets;

use App\Enums\BookingStatus;
use App\Enums\Ownership;
use App\Enums\VehicleType;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummarySheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /** Baris-baris judul yang dicetak tebal (diisi saat array() dibangun). */
    private array $boldRows = [];

    public function __construct(
        private readonly array $filters,
        private readonly array $summary,
        private readonly array $perVehicle,
    ) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function array(): array
    {
        $rows = [];
        $add = function (array $row, bool $bold = false) use (&$rows) {
            $rows[] = $row;
            if ($bold) {
                $this->boldRows[] = count($rows);
            }
        };

        $add(['Laporan Pemesanan Kendaraan'], true);
        $add(['Periode', Carbon::parse($this->filters['from'])->format('d/m/Y').' s/d '.Carbon::parse($this->filters['to'])->format('d/m/Y')]);
        $add(['Dicetak', now()->format('d/m/Y H:i')]);
        $add([]);

        $add(['Status', 'Jumlah'], true);
        foreach (BookingStatus::cases() as $status) {
            $add([$status->label(), $this->summary['by_status'][$status->value] ?? 0]);
        }
        $add(['Total', $this->summary['total']], true);
        $add([]);

        $add(['Pemakaian (disetujui/selesai)', 'Nilai'], true);
        $add(['Total jam pemakaian', $this->summary['hours']]);
        $add(['Total jarak (km)', $this->summary['km']]);
        $add(['Total BBM (liter)', $this->summary['liters']]);
        $add([]);

        $add(['Kendaraan', 'Merk/Model', 'Jenis', 'Kepemilikan', 'Perjalanan', 'Jam', 'Jarak (km)', 'BBM (liter)'], true);
        foreach ($this->perVehicle as $v) {
            $add([
                $v->plate_number,
                $v->brand_model,
                VehicleType::from($v->type)->label(),
                Ownership::from($v->ownership)->label(),
                (int) $v->trips,
                (float) $v->hours,
                (int) $v->km,
                (float) $v->liters,
            ]);
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): ?array
    {
        $styles = [];
        foreach ($this->boldRows as $row) {
            $styles[$row] = ['font' => ['bold' => true]];
        }
        $styles[1] = ['font' => ['bold' => true, 'size' => 14]];

        return $styles;
    }
}

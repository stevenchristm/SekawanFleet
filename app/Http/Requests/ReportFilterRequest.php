<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Enums\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
{
    /** Batas rentang laporan supaya export tetap ringan. */
    public const MAX_DAYS = 366;

    /** Filter tidak valid: kembali ke laporan default (hindari redirect loop ke URL yang sama). */
    protected $redirectRoute = 'reports.index';

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /** Default: bulan berjalan. */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing([
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->endOfMonth()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'type' => ['nullable', Rule::enum(VehicleType::class)],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $days = Carbon::parse($this->input('from'))->diffInDays(Carbon::parse($this->input('to')));
                if ($days > self::MAX_DAYS) {
                    $validator->errors()->add('to', 'Rentang laporan maksimal 1 tahun.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['from' => 'tanggal awal', 'to' => 'tanggal akhir', 'location_id' => 'lokasi'];
    }

    public function messages(): array
    {
        return ['to.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.'];
    }
}

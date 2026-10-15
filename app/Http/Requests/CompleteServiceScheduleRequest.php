<?php

namespace App\Http\Requests;

use App\Services\ServiceScheduleService;
use Illuminate\Foundation\Http\FormRequest;

class CompleteServiceScheduleRequest extends FormRequest
{
    protected $errorBag = 'completeService';

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        // Odometer saat servis tidak boleh mundur dari odometer terakhir yang tercatat.
        $lastOdometer = app(ServiceScheduleService::class)->currentOdometer($this->route('service')->vehicle_id) ?? 0;

        return [
            'completed_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'odometer_at_service' => ['required', 'integer', 'min:'.$lastOdometer, 'max:9999999'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'workshop' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'completed_date' => 'tanggal selesai',
            'odometer_at_service' => 'odometer saat servis',
            'cost' => 'biaya',
            'workshop' => 'bengkel',
            'notes' => 'catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'integer' => ':Attribute harus berupa angka bulat.',
            'numeric' => ':Attribute harus berupa angka.',
            'completed_date.before_or_equal' => 'Tanggal selesai tidak boleh di masa depan.',
            'odometer_at_service.min' => 'Odometer tidak boleh lebih kecil dari odometer terakhir yang tercatat (:min km).',
        ];
    }
}

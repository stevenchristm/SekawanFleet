<?php

namespace App\Http\Requests;

use App\Enums\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceScheduleRequest extends FormRequest
{
    /** Error ditampung terpisah supaya hanya modal "Jadwalkan Servis" yang terbuka kembali. */
    protected $errorBag = 'schedule';

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ServiceType::class)],
            'scheduled_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'odometer_due' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'description' => ['nullable', 'string', 'max:255'],
            'workshop' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'jenis servis',
            'scheduled_date' => 'tanggal servis',
            'odometer_due' => 'target odometer',
            'description' => 'rencana pekerjaan',
            'workshop' => 'bengkel',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'integer' => ':Attribute harus berupa angka bulat.',
            'scheduled_date.after_or_equal' => 'Tanggal servis tidak boleh di masa lalu.',
        ];
    }
}

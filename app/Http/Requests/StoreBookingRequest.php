<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer',
                Rule::exists('vehicles', 'id')->where('status', VehicleStatus::Available->value)],
            'driver_id' => ['required', 'integer',
                Rule::exists('drivers', 'id')->where('is_active', true)],
            'requester_name' => ['required', 'string', 'max:100'],
            'purpose' => ['required', 'string', 'max:500'],
            'destination' => ['required', 'string', 'max:150'],
            'start_at' => ['required', 'date', 'after_or_equal:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            // Urutan array = urutan level persetujuan (index 0 = level 1)
            'approvers' => ['required', 'array', 'min:2', 'max:5'],
            'approvers.*' => ['required', 'integer', 'distinct',
                Rule::exists('users', 'id')->where('role', UserRole::Approver->value)],
        ];
    }

    public function attributes(): array
    {
        return [
            'vehicle_id' => 'kendaraan',
            'driver_id' => 'driver',
            'requester_name' => 'nama pemohon',
            'purpose' => 'keperluan',
            'destination' => 'tujuan',
            'start_at' => 'waktu mulai',
            'end_at' => 'waktu selesai',
            'approvers' => 'approver',
            'approvers.*' => 'approver',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'exists' => ':Attribute tidak valid atau tidak tersedia.',
            'start_at.after_or_equal' => 'Waktu mulai tidak boleh di masa lalu.',
            'end_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'approvers.min' => 'Minimal :min approver (persetujuan berjenjang).',
            'approvers.max' => 'Maksimal :max level approver.',
            'approvers.*.distinct' => 'Approver tidak boleh sama di dua level.',
        ];
    }
}

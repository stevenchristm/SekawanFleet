<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cek hak & status (BookingPolicy::complete) sebelum validasi data, supaya
        // booking yang belum boleh diselesaikan mendapat 403, bukan pesan validasi.
        return (bool) $this->user()?->can('complete', $this->route('booking'));
    }

    public function rules(): array
    {
        $booking = $this->route('booking');

        return [
            'start_odometer' => ['required', 'integer', 'min:0', 'max:9999999'],
            'end_odometer' => ['required', 'integer', 'gte:start_odometer', 'max:9999999'],
            'fuel_liters' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'returned_at' => ['required', 'date', 'after_or_equal:'.$booking->start_at->toDateTimeString(), 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'start_odometer' => 'odometer awal',
            'end_odometer' => 'odometer akhir',
            'fuel_liters' => 'BBM',
            'returned_at' => 'waktu kembali',
            'notes' => 'catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'integer' => ':Attribute harus berupa angka bulat.',
            'numeric' => ':Attribute harus berupa angka.',
            'end_odometer.gte' => 'Odometer akhir tidak boleh lebih kecil dari odometer awal.',
            'returned_at.after_or_equal' => 'Waktu kembali tidak boleh sebelum waktu mulai pemesanan.',
            'returned_at.before_or_equal' => 'Waktu kembali tidak boleh di masa depan.',
        ];
    }
}

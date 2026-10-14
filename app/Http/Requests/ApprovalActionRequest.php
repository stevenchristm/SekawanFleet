<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hak bertindak dicek oleh BookingApprovalPolicy di controller.
        return true;
    }

    public function rules(): array
    {
        return [
            // Alasan wajib saat menolak, opsional saat menyetujui.
            'note' => [$this->routeIs('approvals.reject') ? 'required' : 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Alasan penolakan wajib diisi.',
            'note.max' => 'Catatan maksimal :max karakter.',
        ];
    }
}

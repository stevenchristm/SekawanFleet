<?php

namespace App\Http\Controllers;

use App\Models\BookingApproval;
use Illuminate\Http\Request;

// Kerangka sementara: isi method dibuat pada tahap ApprovalService.
class ApprovalController extends Controller
{
    public function index()
    {
        return 'TODO: persetujuan saya';
    }

    public function approve(Request $request, BookingApproval $approval)
    {
        abort(501);
    }

    public function reject(Request $request, BookingApproval $approval)
    {
        abort(501);
    }
}

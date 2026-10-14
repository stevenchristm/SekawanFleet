<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

// Kerangka sementara: isi method dibuat pada tahap BookingService.
class BookingController extends Controller
{
    public function index()
    {
        return 'TODO: daftar pemesanan';
    }

    public function create()
    {
        return 'TODO: form pemesanan';
    }

    public function store(Request $request)
    {
        abort(501);
    }

    public function show(Booking $booking)
    {
        return 'TODO: detail pemesanan '.$booking->code;
    }

    public function cancel(Booking $booking)
    {
        abort(501);
    }
}

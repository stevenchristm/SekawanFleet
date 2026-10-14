<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// Kerangka sementara: isi method dibuat pada tahap laporan & export Excel.
class ReportController extends Controller
{
    public function index()
    {
        return 'TODO: laporan';
    }

    public function export(Request $request)
    {
        abort(501);
    }
}

<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceScheduleController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    // Semua role
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])
        ->whereNumber('booking')->name('bookings.show'); // dibatasi lewat Policy nanti

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Tidak ada hapus akun mandiri: akun dikelola perusahaan dan terhubung ke data pemesanan.

    // Admin: kelola pemesanan, laporan, log
    Route::middleware('role:admin')->group(function () {
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings/{booking}/complete', [BookingController::class, 'complete'])->name('bookings.complete');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        // Kendaraan: monitoring pemakaian, BBM, dan jadwal servis
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->whereNumber('vehicle')->name('vehicles.show');
        Route::post('/vehicles/{vehicle}/services', [ServiceScheduleController::class, 'store'])->name('services.store');
        Route::patch('/services/{service}/complete', [ServiceScheduleController::class, 'complete'])->name('services.complete');
        Route::patch('/services/{service}/cancel', [ServiceScheduleController::class, 'cancel'])->name('services.cancel');
    });

    // Approver: persetujuan berjenjang
    Route::middleware('role:approver')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{approval}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{approval}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    });
});

require __DIR__.'/auth.php';

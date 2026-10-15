<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    // Semua role
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])
        ->whereNumber('booking')->name('bookings.show'); // dibatasi lewat Policy nanti

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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
    });

    // Approver: persetujuan berjenjang
    Route::middleware('role:approver')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{approval}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{approval}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    });
});

require __DIR__.'/auth.php';

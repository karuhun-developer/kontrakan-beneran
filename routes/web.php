<?php

use App\Http\Controllers\BranchSwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

/*
 * Admin CMS — staff-facing (super-admin, owner, admin, staff).
 */
Route::middleware(['auth', 'verified', 'role:super-admin|owner|admin|staff'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::put('branch/switch', [BranchSwitchController::class, 'update'])->name('branch.switch');
});

/*
 * Penyewa portal.
 */
Route::middleware(['auth', 'verified', 'role:penyewa'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', PortalDashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';

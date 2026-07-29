<?php

use App\Http\Controllers\BranchSwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\FacilityController;
use App\Http\Controllers\Master\PenyewaController;
use App\Http\Controllers\Master\RoomController;
use App\Http\Controllers\Master\RoomTypeController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

/*
 * Admin CMS — staff-facing (super-admin, owner, admin, staff).
 */
Route::middleware(['auth', 'verified', 'role:super-admin|owner|admin|staff'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::put('branch/switch', [BranchSwitchController::class, 'update'])->name('branch.switch');

    /*
     * Master data (Fase 1). Read for all staff; write gated per permission.
     */
    Route::prefix('master')->group(function () {
        Route::resource('room-types', RoomTypeController::class)
            ->parameters(['room-types' => 'roomType'])
            ->except('show')
            ->middlewareFor(['index', 'edit'], 'permission:roomtype.view')
            ->middlewareFor(['create', 'store', 'update', 'destroy'], 'permission:roomtype.manage');

        Route::resource('facilities', FacilityController::class)
            ->except('show')
            ->middlewareFor(['index', 'edit'], 'permission:facility.view')
            ->middlewareFor(['create', 'store', 'update', 'destroy'], 'permission:facility.manage');

        Route::resource('rooms', RoomController::class)
            ->except('show')
            ->middlewareFor(['index', 'edit'], 'permission:room.view')
            ->middlewareFor(['create', 'store', 'update', 'destroy'], 'permission:room.manage');

        Route::resource('penyewa', PenyewaController::class)
            ->parameters(['penyewa' => 'penyewa'])
            ->except('show')
            ->middlewareFor(['index', 'edit'], 'permission:penyewa.view')
            ->middlewareFor(['create', 'store', 'update', 'destroy'], 'permission:penyewa.manage');
    });
});

/*
 * Penyewa portal.
 */
Route::middleware(['auth', 'verified', 'role:penyewa'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', PortalDashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';

<?php

use App\Http\Controllers\Billing\CashflowCategoryController;
use App\Http\Controllers\Billing\CashflowController;
use App\Http\Controllers\Billing\GatewayWebhookController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\BranchSwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontDesk\BookingController;
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

    /*
     * Front desk (Fase 2). Booking date-range with availability guard.
     */
    Route::resource('bookings', BookingController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middlewareFor(['index', 'show'], 'permission:booking.view')
        ->middlewareFor(['create', 'store'], 'permission:booking.manage');

    Route::middleware('permission:booking.manage')->group(function () {
        Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn'])->name('bookings.check-in');
        Route::post('bookings/{booking}/check-out', [BookingController::class, 'checkOut'])->name('bookings.check-out');
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('bookings/{booking}/extend', [BookingController::class, 'extend'])->name('bookings.extend');

        Route::post('penyewa/quick', [PenyewaController::class, 'quickStore'])->name('penyewa.quick-store');
    });

    /*
     * Billing (Fase 3). Invoices, payments, cash book.
     */
    Route::middleware('permission:invoice.view')->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    });

    Route::middleware('permission:invoice.manage')->group(function () {
        Route::post('invoices/from-booking/{booking}', [InvoiceController::class, 'storeFromBooking'])->name('invoices.from-booking');
        Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::post('invoices/{invoice}/checkout', [InvoiceController::class, 'checkout'])->name('invoices.checkout');
        Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
    });

    Route::resource('cashflows', CashflowController::class)
        ->except('show')
        ->middlewareFor(['index'], 'permission:cashflow.view')
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:cashflow.manage');

    Route::resource('cashflow-categories', CashflowCategoryController::class)
        ->parameters(['cashflow-categories' => 'cashflowCategory'])
        ->except('show')
        ->middlewareFor(['index'], 'permission:cashflow.view')
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:cashflow.manage');
});

/*
 * Payment gateway webhook (unauthenticated; verified by driver signature).
 */
Route::post('webhooks/payment', [GatewayWebhookController::class, 'handle'])->name('webhooks.payment');

/*
 * Penyewa portal.
 */
Route::middleware(['auth', 'verified', 'role:penyewa'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', PortalDashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';

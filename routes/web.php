<?php

use App\Http\Controllers\Billing\CashflowCategoryController;
use App\Http\Controllers\Billing\CashflowController;
use App\Http\Controllers\Billing\GatewayWebhookController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\BranchSwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Deposit\DepositController;
use App\Http\Controllers\FrontDesk\BookingController;
use App\Http\Controllers\Laundry\LaundryController;
use App\Http\Controllers\Master\FacilityController;
use App\Http\Controllers\Master\PenyewaController;
use App\Http\Controllers\Master\RoomController;
use App\Http\Controllers\Master\RoomTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\DepositController as PortalDepositController;
use App\Http\Controllers\Portal\InvoiceController as PortalInvoiceController;
use App\Http\Controllers\Portal\LaundryController as PortalLaundryController;
use App\Http\Controllers\Portal\NotificationController as PortalNotificationController;
use App\Http\Controllers\Portal\StayController as PortalStayController;
use App\Http\Controllers\ReportController;
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

        Route::post('penyewa/{penyewa}/account', [PenyewaController::class, 'account'])
            ->name('penyewa.account')
            ->middleware('permission:penyewa.manage');
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

    /*
     * Deposit (Fase 4). Held balances per penyewa with a deduction/refund ledger.
     */
    Route::resource('deposits', DepositController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middlewareFor(['index', 'show'], 'permission:deposit.view')
        ->middlewareFor(['create', 'store'], 'permission:deposit.manage');

    Route::post('deposits/{deposit}/transaction', [DepositController::class, 'transaction'])
        ->name('deposits.transaction')
        ->middleware('permission:deposit.manage');

    /*
     * Laundry (Fase 4). Orders charged to a deposit or raised as an invoice.
     */
    Route::resource('laundry', LaundryController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['laundry' => 'laundry'])
        ->middlewareFor(['index', 'show'], 'permission:laundry.view')
        ->middlewareFor(['create', 'store'], 'permission:laundry.manage');

    Route::post('laundry/{laundry}/advance', [LaundryController::class, 'advance'])
        ->name('laundry.advance')
        ->middleware('permission:laundry.manage');

    /*
     * Notification center (Fase 5). Each user sees only their own notifications.
     */
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    /*
     * Reports (Fase 7). Management-level financial summary + CSV export.
     */
    Route::middleware('permission:report.view')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'exportCashflow'])->name('reports.export');
    });
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

    Route::get('invoices', [PortalInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{invoice}/checkout', [PortalInvoiceController::class, 'checkout'])->name('invoices.checkout');

    Route::get('deposits', [PortalDepositController::class, 'index'])->name('deposits.index');
    Route::get('deposits/{deposit}', [PortalDepositController::class, 'show'])->name('deposits.show');

    Route::get('laundry', [PortalLaundryController::class, 'index'])->name('laundry.index');
    Route::get('laundry/{laundry}', [PortalLaundryController::class, 'show'])->name('laundry.show');

    Route::get('stays', [PortalStayController::class, 'index'])->name('stays.index');
    Route::get('stays/{stay}', [PortalStayController::class, 'show'])->name('stays.show');

    Route::get('notifications', [PortalNotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [PortalNotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [PortalNotificationController::class, 'markRead'])->name('notifications.read');
});

require __DIR__.'/settings.php';

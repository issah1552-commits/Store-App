<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Inventory\InternalMovementController;
use App\Http\Controllers\Inventory\InvoiceController;
use App\Http\Controllers\Inventory\LocationController;
use App\Http\Controllers\Inventory\OrderController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\TransferController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'active.user'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('stores', [LocationController::class, 'index'])->name('stores.index');

    Route::resource('products', ProductController::class)->except(['destroy']);

    Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('transfers/create', [TransferController::class, 'create'])->name('transfers.create');
    Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');
    Route::post('transfers/{transfer}/approve', [TransferController::class, 'approve'])->name('transfers.approve');
    Route::post('transfers/{transfer}/dispatch', [TransferController::class, 'dispatch'])->name('transfers.dispatch');
    Route::post('transfers/{transfer}/receive', [TransferController::class, 'receive'])->name('transfers.receive');
    Route::post('transfers/{transfer}/close', [TransferController::class, 'close'])->name('transfers.close');

    Route::get('internal-movements', [InternalMovementController::class, 'index'])->name('internal-movements.index');
    Route::get('internal-movements/create', [InternalMovementController::class, 'create'])->name('internal-movements.create');
    Route::post('internal-movements', [InternalMovementController::class, 'store'])->name('internal-movements.store');
    Route::get('internal-movements/{internalMovement}', [InternalMovementController::class, 'show'])->name('internal-movements.show');
    Route::post('internal-movements/{internalMovement}/approve', [InternalMovementController::class, 'approve'])->name('internal-movements.approve');
    Route::post('internal-movements/{internalMovement}/reject', [InternalMovementController::class, 'reject'])->name('internal-movements.reject');
    Route::post('internal-movements/{internalMovement}/reverse', [InternalMovementController::class, 'reverse'])->name('internal-movements.reverse');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

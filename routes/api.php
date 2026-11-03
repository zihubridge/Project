<?php

use App\Http\Controllers\Api\V1\AddressValidationController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\SwapOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('throttle:swap-catalog')->group(function () {
        Route::get('/networks', [CatalogController::class, 'networks'])->name('networks');
        Route::get('/assets', [CatalogController::class, 'assets'])->name('assets');
        Route::post('/addresses/validate', [AddressValidationController::class, 'validateAddress'])->name('addresses.validate');
    });

    Route::middleware('throttle:swap-quotes')->group(function () {
        Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
    });

    Route::middleware('throttle:swap-orders')->group(function () {
        Route::get('/swaps', [SwapOrderController::class, 'index'])->name('swaps.index');
        Route::post('/swaps', [SwapOrderController::class, 'store'])->name('swaps.store');
        Route::get('/swaps/{uuid}', [SwapOrderController::class, 'show'])->name('swaps.show');
        Route::post('/swaps/{uuid}/deposit-tx', [SwapOrderController::class, 'submitDepositTx'])->name('swaps.depositTx');
    });
});


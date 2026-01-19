<?php

use App\Http\Controllers\GlobalController;
use App\Http\Controllers\SwapController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('newIndex');
})->name('home');

Route::get('/exchange', function (Request $request) {
    $request->validate([
        'fromasset' => 'required|exists:blockchains,asset_code',
        'toasset'   => 'required|exists:blockchains,asset_code|different:fromasset',
    ]);

    $memo = (string) random_int(100000000, 999999999);

    return view('pages.exchange', [
        'fromAsset' => $request->query('fromasset'),
        'toAsset'   => $request->query('toasset'),
        'memo'   => $memo,
    ]);
})->name('exchange');

Route::get('/deposit', function () {
    return view('pages.deposit');
})->name("deposit");


Route::prefix('global')->group(function () {
    Route::get('blockchains', [GlobalController::class, 'blockchains'])->name('global.blockchains');
    Route::post('tokens', [GlobalController::class, 'tokens'])->name('global.tokens');
    Route::post('token_swapping_amount', [GlobalController::class, 'tokenSwappingAmount'])->name('global.tokenSwappingAmount');
    Route::post('destination_wallet', [GlobalController::class, 'destinationWallet'])->name('global.destinationWallet');
});

Route::prefix('swap')->group(function () {
    Route::post('start', [SwapController::class, 'start'])->name('swap.start');
});

<?php

use App\Http\Controllers\GlobalController;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('newIndex');
})->name('home');

Route::get('/exchange', function (Request $request) {
    $request->validate([
        'from' => 'required|exists:blockchains,id',
        'to'   => 'required|exists:blockchains,id|different:from',
    ]);

    return view('pages.exchange', [
        'fromBlockchainId' => $request->from,
        'toBlockchainId'   => $request->to,
    ]);
})->name('exchange');

Route::get('/deposit', function () {
    return view('pages.deposit');
})->name("deposit");


Route::prefix('global')->group(function () {
    Route::get('blockchains', [GlobalController::class, 'blockchains'])->name('global.blockchains');
    Route::post('tokens', [GlobalController::class, 'tokens'])->name('global.tokens');
});

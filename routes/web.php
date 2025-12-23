<?php

use App\Http\Controllers\GlobalController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('newIndex');
})->name('home');

Route::get('/exchange', function () {
    return view('pages.exchange');
})->name("exchange");

Route::get('/deposit', function () {
    return view('pages.deposit');
})->name("deposit");


Route::prefix('global')->group(function () {
    Route::get('blockchains', [GlobalController::class, 'blockchains'])
        ->name('global.blockchains');
});

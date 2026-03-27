<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\GlobalController;
use App\Http\Controllers\SwapController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return view('index');
})->name('home');

Route::get('/exchange', function (Request $request) {
    $request->validate([
        'fromasset' => 'required|exists:blockchains,asset_code',
        'toasset'   => 'required|exists:blockchains,asset_code|different:fromasset',
    ]);

    return view('pages.exchange', [
        'fromAsset' => $request->query('fromasset'),
        'toAsset'   => $request->query('toasset'),
    ]);
})->name('exchange');

Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/about', 'pages.about')->name('about');
Route::view('contact', 'pages.contact')->name('contact');

Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');

Route::get('/whitepaper', function () {
    return response()->file(public_path('whitepaper.pdf'));
})->name('whitepaper');

Route::prefix('global')->group(function () {
    Route::get('blockchains', [GlobalController::class, 'blockchains'])->name('global.blockchains');
    Route::post('tokens', [GlobalController::class, 'tokens'])->name('global.tokens');
    Route::post('token_swapping_amount', [GlobalController::class, 'tokenSwappingAmount'])->name('global.tokenSwappingAmount');
    Route::post('destination_wallet', [GlobalController::class, 'destinationWallet'])->name('global.destinationWallet');
    Route::get('estimated_swap_time', [GlobalController::class, 'getEstimatedSwapTimeHuman'])->name('global.getEstimatedSwapTimeHuman');
});

Route::prefix('swap')->group(function () {
    Route::post('start', [SwapController::class, 'start'])->name('swap.start');
    Route::get('{uuid}/status', [SwapController::class, 'getStatus'])->name('swap.status');
    Route::get('{uuid}', [SwapController::class, 'show'])->name('swap.show');
});

Route::get('/test-mail', function () {
    Mail::raw('Test email from ZihuBridge', fn($m) => $m->to('support@zihubridge.com')->subject('Test'));
    return 'Email sent! Check your inbox.';
});

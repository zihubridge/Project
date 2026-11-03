<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\Web\SwapPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SwapPageController::class, 'index'])->name('home');
Route::get('/swap', [SwapPageController::class, 'index'])->name('swap.index');
Route::get('/history', [SwapPageController::class, 'history'])->name('swap.history');
Route::get('/swap/{uuid}', [SwapPageController::class, 'show'])->name('swap.show');

Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/about', 'pages.about')->name('about');
Route::view('/contact', 'pages.contact')->name('contact');

Route::post('/contact', [ContactController::class, 'send'])
    ->middleware('throttle:contact-form')
    ->name('contact.send');

Route::get('/whitepaper', function () {
    return response()->file(public_path('whitepaper.pdf'));
})->name('whitepaper');

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

/* ==========================================================================
   Admin Operations Console Routes
   ========================================================================== */

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminBlockchainController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFeeController;
use App\Http\Controllers\Admin\AdminListingController;
use App\Http\Controllers\Admin\AdminRevenueController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminSwapController;
use App\Http\Controllers\Admin\AdminTokenController;

Route::prefix('admin')->name('admin.')->group(function () {
    // Unauthenticated OTP Authentication
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'sendOtp'])->middleware('throttle:10,1')->name('login.send');
    Route::get('/login/verify', [AdminAuthController::class, 'showVerifyForm'])->name('login.verify');
    Route::post('/login/verify', [AdminAuthController::class, 'verifyOtp'])->middleware('throttle:15,1')->name('login.verify.post');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Protected Operations Console
    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Swap Management
        Route::get('/swaps', [AdminSwapController::class, 'index'])->name('swaps.index');
        Route::get('/swaps/{uuid}', [AdminSwapController::class, 'show'])->name('swaps.show');

        // Revenue Accounting
        Route::get('/revenue', [AdminRevenueController::class, 'index'])->name('revenue.index');
        Route::get('/revenue/export', [AdminRevenueController::class, 'export'])->name('revenue.export');

        // Platform Fees
        Route::get('/platform-fees', [AdminFeeController::class, 'index'])->name('fees.index');
        Route::post('/platform-fees', [AdminFeeController::class, 'update'])->name('fees.update');

        // Blockchains
        Route::get('/blockchains', [AdminBlockchainController::class, 'index'])->name('blockchains.index');
        Route::get('/blockchains/{id}/edit', [AdminBlockchainController::class, 'edit'])->name('blockchains.edit');
        Route::put('/blockchains/{id}', [AdminBlockchainController::class, 'update'])->name('blockchains.update');

        // Token Catalog
        Route::get('/tokens', [AdminTokenController::class, 'index'])->name('tokens.index');
        Route::get('/tokens/create', [AdminTokenController::class, 'create'])->name('tokens.create');
        Route::post('/tokens', [AdminTokenController::class, 'store'])->name('tokens.store');
        Route::get('/tokens/{id}/edit', [AdminTokenController::class, 'edit'])->name('tokens.edit');
        Route::put('/tokens/{id}', [AdminTokenController::class, 'update'])->name('tokens.update');

        // Token Listing Pipeline
        Route::get('/listings', [AdminListingController::class, 'index'])->name('listings.index');
        Route::get('/listings/{id}', [AdminListingController::class, 'show'])->name('listings.show');
        Route::put('/listings/{id}/status', [AdminListingController::class, 'updateStatus'])->name('listings.updateStatus');

        // System Settings & Governance
        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/admins', [AdminSettingsController::class, 'storeAdmin'])->name('settings.storeAdmin');
        Route::post('/settings/admins/{id}/toggle', [AdminSettingsController::class, 'toggleAdminStatus'])->name('settings.toggleAdmin');
    });
});

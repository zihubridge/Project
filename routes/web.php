<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('newIndex');
});

Route::get('/exchange', function () {
    return view('pages.exchange');
})->name("exchange");

Route::get('/deposit', function () {
    return view('pages.deposit');
})->name("deposit");

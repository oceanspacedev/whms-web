<?php

use App\Filament\Auth\Pages\PhoneLogin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/phone-login', PhoneLogin::class)->name('phone-login');

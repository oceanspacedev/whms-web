<?php

use App\Filament\Auth\Pages\PhoneLogin;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('admin');
});

Route::get('/phone-login', PhoneLogin::class)
    ->middleware([
        DisableBladeIconComponents::class,
        DispatchServingFilamentEvent::class,
    ])
    ->name('phone-login');

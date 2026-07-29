<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\Landlord\ContactController;
use App\Http\Middleware\PreventAccessFromTenantDomains;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central (Landlord) Routes
|--------------------------------------------------------------------------
|
| These routes are for the central application (super admin panel).
| They should be accessed from the central domain only.
|
| NOTE: Tenant routes are automatically loaded from routes/tenant.php
| by the TenancyServiceProvider and should NOT be included here.
|
*/

// Landlord routes (prevent access from tenant domains)
Route::middleware([
    'web',
    PreventAccessFromTenantDomains::class,
])->group(function () {
    // Landlord panel routes
    require __DIR__ . '/landlord.php';

    // Landing page (#21)
    Route::get('/', [LandingController::class, 'index'])->name('landing');

    // Contact form (public)
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,60');
});

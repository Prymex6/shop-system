<?php

use App\Http\Controllers\Landlord\AuthController;
use App\Http\Controllers\Landlord\ContactController;
use App\Http\Controllers\Landlord\DashboardController;
use App\Http\Controllers\Landlord\LandlordSupportController;
use App\Http\Controllers\Landlord\ModificationController;
use App\Http\Controllers\Landlord\PlanController;
use App\Http\Controllers\Landlord\ShopSearchController;
use App\Http\Controllers\Landlord\TenantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landlord Routes (Super Admin Panel)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('landlord.')->group(function () {
    // Authentication
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,60,super-admin-login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Protected routes
    Route::middleware(['auth.super_admin'])->group(function () {
        // Broadcasting auth for landlord WebSockets
        Route::post('broadcasting/auth', function (Request $request) {
            $channelName = $request->input('channel_name', '');
            $socketId = $request->input('socket_id', '');
            if ($channelName !== 'private-support-admin') {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            $secret = config('broadcasting.connections.reverb.secret');
            $signature = hash_hmac('sha256', "{$socketId}:{$channelName}", $secret);
            $appKey = config('broadcasting.connections.reverb.key');

            return response()->json(['auth' => "{$appKey}:{$signature}"]);
        })->name('landlord.broadcasting.auth');

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Tenants Management
        Route::resource('tenants', TenantController::class);
        Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/cancel-deletion', [TenantController::class, 'cancelDeletion'])->name('tenants.cancel-deletion');
        Route::post('tenants/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('tenants.impersonate');
        Route::post('tenants/{tenant}/clear-cache', [TenantController::class, 'clearCache'])->name('tenants.clear-cache');

        // Modifications (OCMod-like system)
        Route::resource('modifications', ModificationController::class)->except(['show']);
        Route::post('modifications/{modification}/toggle', [ModificationController::class, 'toggle'])->name('modifications.toggle');
        Route::post('modifications/apply', [ModificationController::class, 'apply'])->name('modifications.apply');
        Route::post('modifications/clear', [ModificationController::class, 'clear'])->name('modifications.clear');

        // Plans
        Route::resource('plans', PlanController::class)->except(['show']);

        // Shop search (prospecting tool)
        Route::get('shop-search', [ShopSearchController::class, 'index'])->name('shop-search.index');
        Route::get('shop-search/search', [ShopSearchController::class, 'search'])->name('shop-search.search');
        Route::get('shop-search/find-contact', [ShopSearchController::class, 'findContact'])->name('shop-search.find-contact');
        Route::post('shop-search/toggle-contacted', [ShopSearchController::class, 'toggleContacted'])->name('shop-search.toggle-contacted');

        // Contact inquiries from landing page
        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::patch('contacts/{inquiry}/read', [ContactController::class, 'markRead'])->name('contacts.read');
        Route::delete('contacts/{inquiry}', [ContactController::class, 'destroy'])->name('contacts.destroy');

        // Support tickets (#25)
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', [LandlordSupportController::class, 'index'])->name('index');
            Route::get('/{ticket}', [LandlordSupportController::class, 'show'])->name('show');
            Route::patch('/{ticket}/status', [LandlordSupportController::class, 'updateStatus'])->name('update-status');
            Route::post('/{ticket}/reply', [LandlordSupportController::class, 'reply'])->name('reply');
        });
    });
});

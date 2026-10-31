<?php

use App\Http\Controllers\RoyalAdminController;
use App\Http\Controllers\WeddingOrderController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'royal-app');
Route::view('/admin', 'royal-app');

Route::prefix('api')->group(function () {
    // Public order lookup is intentional per V6; throttle to mitigate abuse.
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/orders/open', [WeddingOrderController::class, 'open']);
        Route::put('/orders/{orderNumber}', [WeddingOrderController::class, 'save']);
        Route::post('/orders/{orderNumber}/submit', [WeddingOrderController::class, 'submit']);
        Route::post('/orders/{orderNumber}/photos', [WeddingOrderController::class, 'upload']);
        Route::get('/orders/{orderNumber}/photos/{photo}', [WeddingOrderController::class, 'photo']);
        Route::delete('/orders/{orderNumber}/photos/{photo}', [WeddingOrderController::class, 'removePhoto']);
    });

    Route::post('/admin/login', [RoyalAdminController::class, 'login'])->middleware('throttle:5,1');
    Route::middleware('auth')->prefix('admin')->group(function () {
        Route::get('/me', [RoyalAdminController::class, 'me']);
        Route::post('/logout', [RoyalAdminController::class, 'logout']);
        Route::get('/orders', [RoyalAdminController::class, 'index']);
        Route::get('/notifications', [RoyalAdminController::class, 'notifications']);
        Route::get('/orders/{order}', [RoyalAdminController::class, 'show']);
        Route::patch('/orders/{order}', [RoyalAdminController::class, 'update']);
        Route::delete('/orders/{order}', [RoyalAdminController::class, 'destroy']);
        Route::post('/orders/{order}/seen', [RoyalAdminController::class, 'markSeen']);
        Route::get('/orders/{order}/export', [RoyalAdminController::class, 'export']);
        Route::get('/orders/{order}/photos/{photo}', [RoyalAdminController::class, 'photo']);
    });
});

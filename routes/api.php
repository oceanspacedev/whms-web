<?php

use App\Http\Controllers\Api\V1\TrackingOrderApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Courier helpers
    Route::get('/courier/drivers', [TrackingOrderApiController::class, 'drivers']);
    Route::get('/courier/summary', [TrackingOrderApiController::class, 'courierSummary']);

    // Tracking orders for couriers
    Route::get('/tracking-orders', [TrackingOrderApiController::class, 'index']);
    Route::get('/tracking-orders/by-sj/{no_sj}', [TrackingOrderApiController::class, 'findByNoSj']);
    Route::get('/tracking-orders/{trackingOrder}', [TrackingOrderApiController::class, 'show']);

    // Mobile Proof of Delivery (POD) photo upload & submission
    Route::post('/tracking-orders/{trackingOrder}/submit-pod', [TrackingOrderApiController::class, 'submitPod']);
    Route::post('/tracking-orders/by-sj/{no_sj}/submit-pod', [TrackingOrderApiController::class, 'submitPodBySj']);
});

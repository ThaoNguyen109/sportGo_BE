<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API OK'
    ]);
});

/**
 * Authentication
 */
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:api')->get('/me', [AuthController::class, 'me']);

/**
 * Court Routes
 * 
 * GET /api/courts/{id} - Get court detail by ID
 * GET /api/courts/{id}/prices - Get field prices for a court
 * 
 * Pattern: RESTful API design
 * Reason: Standard convention for web APIs
 * 
 * SOLID: Single Responsibility
 * Route maps URL to controller action
 * Controller handles HTTP, Service handles business logic
 * 
 * Example:
 * GET /api/courts/1
 * Response: Court detail with owner, fields, images
 * 
 * GET /api/courts/1/prices
 * Response: Field prices with time slots and pricing
 */
Route::get('/courts/{id}', [CourtController::class, 'show']);
Route::get('/courts/{id}/prices', [CourtController::class, 'getFieldPrices']);


// Routes cần đăng nhập
Route::middleware('auth:api')->group(function () {
    Route::post('/bookings/reserve', [BookingController::class, 'reserve']);
    Route::delete('/bookings/cancel/{id}',  [BookingController::class, 'cancel']);
    Route::get('/slots/status',      [BookingController::class, 'checkSlotStatus']);
});

if (app()->environment('local') || env('ENABLE_TEST_LOGIN', false)) {
    Route::post('/test-login', [AuthController::class, 'fakeLogin']);
}

// Không cần auth — ai cũng xem được lịch sân
Route::get('/courts/{id}/slots', [CourtController::class, 'getSlots']);

// Webhook thanh toán (không cần auth user)
Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);

// Tạo link thanh toán (cần đăng nhập)
Route::middleware('auth:api')->group(function () {
    Route::post('/payments/momo/create', [PaymentController::class, 'createMomoPayment']);
});

// MoMo callback — KHÔNG cần auth
Route::post('/payments/momo/ipn',    [PaymentController::class, 'momoIpn']);
Route::get('/payments/momo/return',  [PaymentController::class, 'momoReturn']);


<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — SportGo
|--------------------------------------------------------------------------
*/

// ─── Health check ─────────────────────────────────────────────────────────
Route::get('/test', fn () => response()->json(['message' => 'API OK']));

// ─── Auth (không cần đăng nhập) ───────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

/**
 * Authentication
 */
// Alias backward-compatible: /api/login → /api/auth/login (giữ để không vỡ Postman cũ)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:api')->get('/me', [AuthController::class, 'me']);

// ─── Auth (cần đăng nhập) ─────────────────────────────────────────────────
Route::prefix('auth')->middleware('auth:api')->group(function () {
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me',       [AuthController::class, 'me']);
});

// ─── Courts (công khai — ai cũng xem được) ────────────────────────────────
Route::prefix('courts')->group(function () {
    Route::get('/',              [CourtController::class, 'index']);
    Route::get('/{id}',          [CourtController::class, 'show']);
    Route::get('/{id}/prices',   [CourtController::class, 'getFieldPrices']);
    Route::get('/{id}/slots',    [CourtController::class, 'getSlots']);
});

// Trạng thái slot (công khai)
Route::get('/slots/status', [BookingController::class, 'checkSlotStatus']);

// ─── Bookings (cần đăng nhập) ─────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {
    Route::get('/bookings',          [BookingController::class, 'index']);   // Lịch sử đặt sân
    Route::get('/bookings/{id}',     [BookingController::class, 'show']);    // Chi tiết booking
    Route::post('/bookings/reserve', [BookingController::class, 'reserve']); // Đặt sân
    Route::delete('/bookings/{id}',  [BookingController::class, 'cancel']);  // Huỷ booking
    Route::post('/bookings/{id}/refund', [BookingController::class, 'requestRefund']); // Yêu cầu hoàn tiền
});

// ─── Payments (cần đăng nhập) ─────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {
    Route::post('/payments/momo/create', [PaymentController::class, 'createMomoPayment']);
});

// MoMo callback — KHÔNG cần auth (MoMo server gọi)
Route::post('/payments/momo/ipn',    [PaymentController::class, 'momoIpn']);
Route::get('/payments/momo/return',  [PaymentController::class, 'momoReturn']);

// ─── Dev only: fake login không cần password ──────────────────────────────
// if (app()->environment('local') || env('ENABLE_TEST_LOGIN', false)) {
//     Route::post('/test-login', [AuthController::class, 'fakeLogin']);
// }

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OwnerCourtController;
use App\Http\Controllers\AdminCourtController;
use App\Http\Controllers\OwnerBookingController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminPayoutController;
use App\Http\Controllers\OwnerPayoutController;
use App\Http\Controllers\OwnerBankAccountController;
use App\Http\Controllers\AdminOwnerBankAccountController;
use App\Http\Controllers\NotificationController;
use App\Events\NewNotificationEvent;
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

// Alias backward-compatible: /api/login → /api/auth/login
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ─── Auth (cần đăng nhập)
Route::prefix('auth')->middleware('auth:api')->group(function () {
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me',       [AuthController::class, 'me']);
});

// ─── Admin quick actions
Route::middleware('auth:api')->prefix('admin')->group(function () {
    Route::patch('/users/{id}/toggle-status', [AdminUserController::class, 'toggleStatus']);
});

// ─── Courts (công khai)
Route::prefix('courts')->group(function () {
    Route::get('/',              [CourtController::class, 'index']);
    Route::get('/{id}',          [CourtController::class, 'show']);
    Route::get('/{id}/prices',   [CourtController::class, 'getFieldPrices']);
    Route::get('/{id}/slots',    [CourtController::class, 'getSlots']);
});

// Trạng thái slot (công khai)
Route::get('/slots/status', [BookingController::class, 'checkSlotStatus']);

// ─── Owner - quản lý sân
Route::middleware('auth:api')->prefix('owner')->group(function () {
    Route::post('/courts', [OwnerCourtController::class, 'createCourt']);
    Route::get('/courts', [OwnerCourtController::class, 'getMyCourts']);
    Route::get('/courts/{id}', [OwnerCourtController::class, 'getMyCourtDetail']);
    Route::put('/courts/{id}', [OwnerCourtController::class, 'updateCourt']);
    Route::post('/courts/{id}/images', [OwnerCourtController::class, 'uploadCourtImage']);
    Route::delete('/courts/{courtId}/images/{imageId}', [OwnerCourtController::class, 'deleteCourtImage']);
    Route::post('/courts/{courtId}/fields', [OwnerCourtController::class, 'addField']);
    Route::put('/fields/{fieldId}', [OwnerCourtController::class, 'updateField']);
    Route::put('/prices/{fieldId}', [OwnerCourtController::class, 'updateFieldPrices']);
    Route::get('/bookings', [OwnerBookingController::class, 'getCourtBookings']);
    Route::get('/bookings/{id}', [OwnerBookingController::class, 'getBookingDetail'])->whereNumber('id');
    Route::post('/bookings/{id}/cancel', [OwnerBookingController::class, 'cancelBooking']);
    Route::get('/bookings/dashboard',[OwnerBookingController::class, 'dashboard']);
    Route::get('/bookings/stats', [OwnerBookingController::class, 'bookingStats']);
    Route::get('/bookings/revenue', [OwnerBookingController::class, 'getRevenue']);
    Route::get('/bookings/{courtId}/calendar', [OwnerBookingController::class, 'courtCalendar']);
    Route::get('/payouts', [OwnerPayoutController::class, 'getMyPayouts']);
    Route::post('/bank-account', [OwnerBankAccountController::class,'saveBankAccount']);
    Route::get('/bank-account', [OwnerBankAccountController::class, 'getMyBankAccount']);
});

// ─── Admin - Duyệt sân
Route::middleware('auth:api')->prefix('admin')->group(function () {
    Route::get('/courts/pending', [AdminCourtController::class, 'getPendingCourts']);
    Route::put('/courts/{id}/approve', [AdminCourtController::class, 'approveCourt']);
    Route::put('/courts/{id}/reject', [AdminCourtController::class, 'rejectCourt']);
    Route::put('/courts/{id}/toggle-active', [AdminCourtController::class, 'toggleActive']);
    Route::get('/courts', [AdminCourtController::class, 'getAllCourts']);
    Route::get('/courts/{id}', [AdminCourtController::class, 'getCourtDetail'])->where('id', '[0-9]+');
    Route::get('/courts/stats', [AdminCourtController::class, 'getCourtStats']);
    Route::get('/bookings',[AdminBookingController::class, 'getAllBookings']);
    Route::get('/bookings/stats', [AdminBookingController::class, 'getBookingStats']);
    Route::get('/bookings/{id}', [AdminBookingController::class, 'getBookingDetail'])->whereNumber('id');
    Route::patch('/bookings/{id}/refund',[AdminBookingController::class, 'refundBooking']);
    Route::get('/bookings/revenue', [AdminBookingController::class, 'getRevenue']);
    Route::get('/users', [AdminUserController::class, 'getAllUsers']);
    Route::get('/payouts/pending-owners', [AdminPayoutController::class, 'getAllOwnersPendingPayout']);
    Route::get('/payouts/pending/{ownerId}',[AdminPayoutController::class, 'getPendingPayoutByOwner'])->whereNumber('ownerId');
    Route::post('/payouts/{ownerId}',[AdminPayoutController::class, 'createPayout'])->whereNumber('ownerId');
    Route::get('/payouts', [AdminPayoutController::class, 'getPayouts']);
    Route::post('/payouts/{id}/pay', [AdminPayoutController::class, 'payPayout'])->whereNumber('id');
    Route::get('/payouts/{id}',[AdminPayoutController::class, 'getPayoutDetail'])->whereNumber('id');
    Route::get('/owners/{ownerId}/bank-account', [AdminOwnerBankAccountController::class, 'getOwnerBankAccount'])->whereNumber('ownerId');
});

// ─── Notifications
Route::middleware('auth:api')->group(function () {
    Route::get('/notifications',[NotificationController::class, 'index']);
    Route::post('/notifications/{id}/mark-as-read',[NotificationController::class, 'markAsRead'])->whereNumber('id');
});

Route::get('/test-realtime', function () {
    $notification = \App\Models\Notification::create([
        'user_id' => 1,
        'title' => 'Test realtime',
        'content' => 'Hello từ backend',
        'type' => 'test'
    ]);

    event(new NewNotificationEvent($notification));

    return 'Sent!';
});

// ─── Payments (cần đăng nhập)
Route::middleware('auth:api')->group(function () {
    Route::post('/payments/momo/create', [PaymentController::class, 'createMomoPayment']);
});

// MoMo callback — KHÔNG cần auth (MoMo server gọi)
Route::post('/payments/momo/ipn',    [PaymentController::class, 'momoIpn']);
Route::get('/payments/momo/return',  [PaymentController::class, 'momoReturn']);

// ─── Bookings (cần đăng nhập)
Route::middleware('auth:api')->group(function () {
    Route::get('/bookings',          [BookingController::class, 'index']);
    Route::get('/bookings/{id}',     [BookingController::class, 'show']);
    Route::post('/bookings/reserve', [BookingController::class, 'reserve']);
    Route::delete('/bookings/{id}',  [BookingController::class, 'cancel']);
    Route::post('/bookings/{id}/refund', [BookingController::class, 'requestRefund']);
});

// ─── Dev only: fake login không cần password
// if (app()->environment('local') || env('ENABLE_TEST_LOGIN', false)) {
//     Route::post('/test-login', [AuthController::class, 'fakeLogin']);
// }

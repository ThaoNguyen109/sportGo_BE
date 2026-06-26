<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourtController;
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

Route::middleware('auth:api')->prefix('admin')->group(function () {
    Route::patch('/users/{id}/toggle-status', [AdminUserController::class, 'toggleStatus']);
});


/*
|--------------------------------------------------------------------------
| USER - Xem sân
|--------------------------------------------------------------------------
*/

// Danh sách sân đã duyệt
Route::get('/courts', [CourtController::class, 'getApprovedCourts']);

// Chi tiết sân
Route::get('/courts/{id}', [CourtController::class, 'getCourtDetail']);

// Tìm sân theo giờ
Route::get('/courts/search', [CourtController::class, 'searchCourt']);


/*
|--------------------------------------------------------------------------
| OWNER - Quản lý sân
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->prefix('owner')->group(function () {

    // tạo sân có cả sân con và giá luôn
    Route::post('/courts', [OwnerCourtController::class, 'createCourt']);
    // lấy danh sách sân của owner
    Route::get('/courts', [OwnerCourtController::class, 'getMyCourts']);
    // lấy chi tiết sân của owner (có cả sân con và giá)
    Route::get('/courts/{id}', [OwnerCourtController::class, 'getMyCourtDetail']);

    // Cập nhật thông tin sân (không cập nhật hình ảnh, sân con, giá, )
    Route::put('/courts/{id}', [OwnerCourtController::class, 'updateCourt']);
   // upload hình ảnh sân
    Route::post('/courts/{id}/images', [OwnerCourtController::class, 'uploadCourtImage']);
    // xóa hình ảnh sân
    Route::delete('/courts/{courtId}/images/{imageId}', [OwnerCourtController::class, 'deleteCourtImage']);

    // Field
    // Thêm sân con vào sân mẹ đã có (phải thêm cả giá luôn)
    Route::post('/courts/{courtId}/fields', [OwnerCourtController::class, 'addField']);
    // Cập nhật thông tin sân con (không cập nhật giá)
    Route::put('/fields/{fieldId}', [OwnerCourtController::class, 'updateField']);

    // Price
    // Cùng 1 lúc có thể Thêm, sửa, xóa giá vào sân con đã có
    Route::put('/prices/{fieldId}', [OwnerCourtController::class, 'updateFieldPrices']);

    // lấy về danh sách booking thuộc sân owner (có filter status, date)
    Route::get('/bookings', [OwnerBookingController::class, 'getCourtBookings']);
    // lấy chi tiết booking thuộc sân owner
    Route::get('/bookings/{id}', [OwnerBookingController::class, 'getBookingDetail'])->whereNumber('id');
    // hủy booking thuộc sân owner
    Route::post('/bookings/{id}/cancel', [OwnerBookingController::class, 'cancelBooking']);
    // hiển thị dashboard thống kê số liệu sân owner (doanh thu, số booking,)
    Route::get('/bookings/dashboard',[OwnerBookingController::class, 'dashboard']);
    // lấy thống kê booking theo status
    Route::get('/bookings/stats', [OwnerBookingController::class, 'bookingStats']);
    // lấy thống kê doanh thu
    Route::get( '/bookings/revenue', [OwnerBookingController::class, 'getRevenue']);
    // lấy dữ liệu để lịch đặt
    Route::get('/bookings/{courtId}/calendar', [OwnerBookingController::class, 'courtCalendar']);

    // lấy danh sách payout của owner (có filter status, from_date, to_date)
    Route::get('/payouts', [OwnerPayoutController::class, 'getMyPayouts']);

    // Tạo hoặc cập nhật thông tin tài khoản ngân hàng của owner (có upload qr code)
    Route::post('/bank-account', [OwnerBankAccountController::class,'saveBankAccount']);
    // Xem thông tin tài khoản ngân hàng của owner
    Route::get('/bank-account', [OwnerBankAccountController::class, 'getMyBankAccount']);

});


/*
|--------------------------------------------------------------------------
| ADMIN - Duyệt sân
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->prefix('admin')->group(function () {
    // lấy danh sách sân đang chờ duyệt
    Route::get('/courts/pending', [AdminCourtController::class, 'getPendingCourts']);
    // duyệt sân
    Route::put('/courts/{id}/approve', [AdminCourtController::class, 'approveCourt']);
    // từ chối duyệt sân
    Route::put('/courts/{id}/reject', [AdminCourtController::class, 'rejectCourt']);
    // bật/tắt sân
    Route::put('/courts/{id}/toggle-active', [AdminCourtController::class, 'toggleActive']);
    // lấy danh sách tất cả sân (có filter theo status)
    Route::get('/courts', [AdminCourtController::class, 'getAllCourts']);
    // lấy chi tiết sân (có cả sân con và giá)
    Route::get('/courts/{id}', [AdminCourtController::class, 'getCourtDetail'])->where('id', '[0-9]+');
    // thống kê số liệu sân (số lượng sân theo status, số lượng sân theo owner,...)
    Route::get('/courts/stats', [AdminCourtController::class, 'getCourtStats']);
    // lấy danh sách booking của tất cả sân + filter
    Route::get('/bookings',[AdminBookingController::class, 'getAllBookings']);
    // thống kê booking toàn hệ thống và sân có nhiều booking nhất
    Route::get('/bookings/stats', [AdminBookingController::class, 'getBookingStats']);
    // lấy chi tiết booking
    Route::get('/bookings/{id}', [AdminBookingController::class, 'getBookingDetail'])->whereNumber('id');
    // refund booking
    Route::patch('/bookings/{id}/refund',[AdminBookingController::class, 'refundBooking']);
    // thống kê doanh thu toàn hệ thống
    Route::get('/bookings/revenue', [AdminBookingController::class, 'getRevenue']);

    // lấy danh sách tất cả người dùng (trừ admin)
    Route::get('/users', [AdminUserController::class, 'getAllUsers']);
    
    //payout là quá trình thanh toán cho owner, admin sẽ tạo payout sau đó hệ thống sẽ tự động thanh toán vào cuối ngày hoặc cuối tuần, tùy theo cài đặt của hệ thống. 
    // Payout sẽ bao gồm nhiều booking đã hoàn thành và chưa được thanh toán cho owner. Khi tạo payout, admin sẽ chọn owner và các booking cần thanh toán, hệ thống sẽ tính tổng số tiền cần thanh toán dựa trên giá của từng booking và trừ đi phí dịch vụ (5%)
    
    Route::get('/payouts/pending-owners', [AdminPayoutController::class, 'getAllOwnersPendingPayout']);
    // Danh sách booking chưa hoàn tiền cho owner - cái này cho admin xem trước khi tạo payout để biết được những booking nào sẽ được thanh toán
    Route::get('/payouts/pending/{ownerId}',[AdminPayoutController::class, 'getPendingPayoutByOwner'])->whereNumber('ownerId');
    // tạo payout mới lưu vào database, hệ thống sẽ tự động thanh toán vào cuối ngày hoặc cuối tuần
    Route::post('/payouts/{ownerId}',[AdminPayoutController::class, 'createPayout'])->whereNumber('ownerId');
    // lấy danh sách payout đã tạo
    Route::get('/payouts', [AdminPayoutController::class, 'getPayouts']);
    // thanh toán payout ngay lập tức (dành cho trường hợp admin muốn thanh toán ngay mà không cần đợi đến cuối ngày hoặc cuối tuần)
    Route::post('/payouts/{id}/pay', [AdminPayoutController::class, 'payPayout'])->whereNumber('id');
    
    Route::get('/payouts/{id}',[AdminPayoutController::class, 'getPayoutDetail']
);

    // lấy thông tin tài khoản ngân hàng của owner để admin xem khi cần thiết (không có upload qr code)
    Route::get('/owners/{ownerId}/bank-account', [AdminOwnerBankAccountController::class, 'getOwnerBankAccount'])->whereNumber('ownerId');


});

/*
|--------------------------------------------------------------------------
| NOTIFICATIONS
|--------------------------------------------------------------------------
*/
// lấy danh sách notification của MÌNH (chỉ lấy của user đang đăng nhập, có phân trang)
Route::middleware('auth:api')->group(function () {Route::get('/notifications',[NotificationController::class, 'index']);});
// đánh dấu đã đọc notification (chỉ được đánh dấu của user đang đăng nhập)
Route::middleware('auth:api')->group(function () {Route::post('/notifications/{id}/mark-as-read',[NotificationController::class, 'markAsRead'])->whereNumber('id');});


Route::get('/test-realtime', function () {
    $notification = \App\Models\Notification::create([
        'user_id' => 1,
        'title' => 'Test realtime',
        'content' => 'Hello từ backend',
        'type' => 'test'
    ]);

    event(new \App\Events\NewNotificationEvent($notification));

    return 'Sent!';
});
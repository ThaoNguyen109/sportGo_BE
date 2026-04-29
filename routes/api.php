<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API OK'
    ]);
});
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\OwnerCourtController;
use App\Http\Controllers\AdminCourtController;

Route::post('/login', [AuthController::class, 'login']);

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
| OWNER - Quản lý sân (cần login)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('owner')->group(function () {

    // Tạo sân
    Route::post('/courts', [OwnerCourtController::class, 'createCourt']);

    // Cập nhật sân
    Route::put('/courts/{id}', [OwnerCourtController::class, 'updateCourt']);

    // ===== FIELD =====

    // Thêm sân con
    Route::post('/courts/{courtId}/fields', [OwnerCourtController::class, 'addField']);

    // Cập nhật sân con
    Route::put('/fields/{fieldId}', [OwnerCourtController::class, 'updateField']);

    // ===== PRICE =====

    // Thêm giá
    Route::post('/fields/{fieldId}/prices', [OwnerCourtController::class, 'addFieldPrice']);

    // Cập nhật giá
    Route::put('/prices/{priceId}', [OwnerCourtController::class, 'updateFieldPrice']);
});


/*
|--------------------------------------------------------------------------
| ADMIN - Duyệt sân
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('admin')->group(function () {

    // Lấy danh sách sân chờ duyệt
    Route::get('/courts/pending', [AdminCourtController::class, 'getPendingCourts']);

    // Duyệt sân
    Route::put('/courts/{id}/approve', [AdminCourtController::class, 'approveCourt']);

    // Từ chối sân
    Route::put('/courts/{id}/reject', [AdminCourtController::class, 'rejectCourt']);
});
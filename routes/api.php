<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourtController; // giữ thêm cái này
use App\Http\Controllers\OwnerCourtController;
use App\Http\Controllers\AdminCourtController;


Route::get('/test', function () {
    return response()->json([
        'message' => 'API OK'
    ]);
});


/**
 * Authentication Routes
 */
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // giữ của bạn

/**
 * Court Routes
 */
Route::get('/courts/{id}', [CourtController::class, 'show']); // giữ của main

Route::middleware('auth:api')->get('/me', [AuthController::class, 'me']);



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
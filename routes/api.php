<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\OwnerCourtController;
use App\Http\Controllers\AdminCourtController;

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

    Route::post('/courts', [OwnerCourtController::class, 'createCourt']);
    Route::put('/courts/{id}', [OwnerCourtController::class, 'updateCourt']);

    // Field
    Route::post('/courts/{courtId}/fields', [OwnerCourtController::class, 'addField']);
    Route::put('/fields/{fieldId}', [OwnerCourtController::class, 'updateField']);

    // Price
    Route::post('/fields/{fieldId}/prices', [OwnerCourtController::class, 'addFieldPrice']);
    Route::put('/prices/{priceId}', [OwnerCourtController::class, 'updateFieldPrice']);
});


/*
|--------------------------------------------------------------------------
| ADMIN - Duyệt sân
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->prefix('admin')->group(function () {

    Route::get('/courts/pending', [AdminCourtController::class, 'getPendingCourts']);
    Route::put('/courts/{id}/approve', [AdminCourtController::class, 'approveCourt']);
    Route::put('/courts/{id}/reject', [AdminCourtController::class, 'rejectCourt']);
});
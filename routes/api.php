<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourtController; // giữ thêm cái này

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
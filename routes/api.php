<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API OK'
    ]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
// thêm dòng test
// 123 
// 456 

Route::middleware('auth:api')->get('/me', [AuthController::class, 'me']);
// test commit
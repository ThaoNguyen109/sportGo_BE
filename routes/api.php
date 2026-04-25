<?php

use Illuminate\Support\Facades\Route;
Route::get('/test', function () {
    return response()->json([
        'message' => 'API OK'
    ]);
});
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
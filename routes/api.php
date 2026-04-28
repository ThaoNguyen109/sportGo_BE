<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourtController;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API OK'
    ]);
});

/**
 * Authentication Routes
 */
Route::post('/login', [AuthController::class, 'login']);

/**
 * Court Routes
 * 
 * GET /api/courts/{id} - Get court detail by ID
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
 */
Route::get('/courts/{id}', [CourtController::class, 'show']);

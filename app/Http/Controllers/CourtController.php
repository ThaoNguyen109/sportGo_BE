<?php

namespace App\Http\Controllers;

use App\Services\CourtService;
use Exception;
use Illuminate\Http\JsonResponse;

/**
 * CourtController
 * 
 * Pattern: MVC Controller Pattern (standard Laravel)
 * Reason: Handle HTTP requests/responses, delegate business logic to Service
 * 
 * SOLID Principles Applied:
 * - S (Single Responsibility): ONLY handles HTTP concerns
 *   - Validate HTTP input (optional, Service validates business logic)
 *   - Call Service for business logic
 *   - Format HTTP response (status code, headers)
 *   Does NOT contain business logic, data access
 * 
 * - D (Dependency Inversion): Depends on CourtService interface behavior
 *   Service is injected, not instantiated
 * 
 * - O (Open/Closed): New court endpoints can be added without modifying existing methods
 * 
 * Design Pattern: Dependency Injection
 *   Constructor receives CourtService automatically from Laravel Container
 *   Benefit: Easy to test - inject mock service
 */
class CourtController extends Controller
{
    /**
     * Service instance
     * Handles all court business logic
     */
    private CourtService $courtService;

    /**
     * Constructor with Dependency Injection
     * 
     * SOLID: Dependency Inversion
     * Laravel's Service Container automatically injects CourtService
     * Benefit: Service is registered in AppServiceProvider.php
     * 
     * @param CourtService $courtService Business logic layer
     */
    public function __construct(CourtService $courtService)
    {
        $this->courtService = $courtService;
    }

    /**
     * Get court detail by ID
     * 
     * Route: GET /api/courts/{id}
     * Response: 200 | 404 | 500
     * 
     * SOLID: Single Responsibility
     * This method:
     * 1. Validates HTTP input (id parameter)
     * 2. Calls Service for business logic
     * 3. Returns HTTP response
     * Does NOT: query database, transform data, check permissions
     * 
     * Pattern: Action/Handler pattern
     * 
     * @param int $id Court ID from URL parameter
     * @return JsonResponse JSON response with court data
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Validate input (basic validation - business rules in Service)
            if ($id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID không hợp lệ'
                ], 400);
            }

            // Call Service for business logic
            // Service handles:
            // - Retrieving from repository
            // - Null checking
            // - Permission checks (future)
            // - Data transformation
            $courtData = $this->courtService->getCourtDetail($id);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin sân thành công',
                'data' => $courtData
            ], 200);

        } catch (Exception $e) {
            // Catch business logic exceptions (e.g., court not found)
            $statusCode = (int) ($e->getCode() ?: 500);

            // Ensure status code is valid HTTP status code (100-599)
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);

        } catch (\Throwable $e) {
            // Catch unexpected errors
            // Log error for debugging
            \Log::error('CourtController@show error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Get field prices for a court
     *
     * Route: GET /api/courts/{id}/prices
     * Response: 200 | 404 | 500
     *
     * SOLID: Single Responsibility
     * This method:
     * 1. Validates HTTP input (court_id parameter)
     * 2. Calls Service for business logic
     * 3. Returns HTTP response
     * Does NOT: query database, transform data, check permissions
     *
     * Pattern: Action/Handler pattern
     *
     * @param int $courtId Court ID from URL parameter
     * @return JsonResponse JSON response with field prices data
     */
    public function getFieldPrices(int $courtId): JsonResponse
    {
        try {
            // Validate input (basic validation - business rules in Service)
            if ($courtId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID sân không hợp lệ'
                ], 400);
            }

            // Call Service for business logic
            // Service handles:
            // - Retrieving from repository
            // - Court existence validation
            // - Permission checks (future)
            // - Data transformation
            $pricesData = $this->courtService->getFieldPrices($courtId);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Lấy giá sân thành công',
                'data' => $pricesData
            ], 200);

        } catch (Exception $e) {
            // Catch business logic exceptions (e.g., court not found)
            $statusCode = (int) ($e->getCode() ?: 500);

            // Ensure status code is valid HTTP status code (100-599)
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);

        } catch (\Throwable $e) {
            // Catch unexpected errors
            \Log::error('CourtController@getFieldPrices error', [
                'court_id' => $courtId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * GET /api/courts/{id}/slots?date=2026-05-10
     * Lấy toàn bộ slot kèm trạng thái để frontend tô màu.
     */
    public function getSlots(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        try {
            $data = $this->courtService->getSlotsByCourt($id, $validated['date']);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);

        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 599)
                ? $e->getCode() : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
}

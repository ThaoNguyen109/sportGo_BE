<?php

namespace App\Http\Controllers;

use App\Services\AdminCourtService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminCourtController extends Controller
{
    public function __construct(
        private AdminCourtService $adminCourtService
    ) {}

    public function getPendingCourts(): JsonResponse
    {
        try {
            $courts = $this->adminCourtService->getPendingCourts();

            return response()->json([
                'message' => 'Lấy danh sách sân chờ duyệt thành công',
                'data' => $courts
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✔ Duyệt sân
    public function approveCourt($id): JsonResponse
    {
        try {
            $court = $this->adminCourtService->approveCourt($id);

            return response()->json([
                'message' => 'Duyệt sân thành công',
                'data' => $court
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Không thể duyệt sân',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ❌ Từ chối sân
    public function rejectCourt(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string|max:255'
            ]);

            $court = $this->adminCourtService->rejectCourt(
                $id,
                $validated['reason'] ?? null
            );

            return response()->json([
                'message' => 'Từ chối sân thành công',
                'data' => $court
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleActive($id): JsonResponse
    {
        try {
            $court = $this->adminCourtService->toggleActive($id);

            return response()->json([
                'message' => 'Cập nhật trạng thái sân thành công',
                'data' => $court
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllCourts(Request $request): JsonResponse
    {
        try {
            $courts = $this->adminCourtService->getAllCourts($request);

            return response()->json([
                'message' => 'Lấy danh sách sân thành công',
                'data' => $courts
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCourtDetail($id): JsonResponse
    {
        try {
            $court = $this->adminCourtService->getCourtDetail($id);

            return response()->json([
                'message' => 'Lấy chi tiết sân thành công',
                'data' => $court
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Không tìm thấy sân'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCourtStats(): JsonResponse
    {
        try {
            $stats = $this->adminCourtService->getCourtStats();

            return response()->json([
                'message' => 'Lấy thống kê thành công',
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}